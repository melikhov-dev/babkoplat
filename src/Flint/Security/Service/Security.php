<?php
namespace Flint\Security\Service;

use Bb8\Service\CityManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Cookie;

use PimpleAwareEventDispatcher\PimpleAwareEventDispatcher;

use Flint\Security\Service\Event\LoginEvent;
use Flint\Security\SecurityInterface;
use Flint\Security\EncoderInterface;
use Flint\Security\UserStorageAdapterInterface;
use Flint\Security\OAuthProviderTypeEnum;

class Security implements SecurityInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var EncoderInterface
     */
    protected $encoder;

    /**
     * @var UserStorageAdapterInterface
     */
    protected $storage;

    /**
     * @var PimpleAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $user;

    protected $schemaId = 'user';

    public function __construct(
        SessionInterface $session,
        EncoderInterface $encoder,
        UserStorageAdapterInterface $storage,
        PimpleAwareEventDispatcher $dispatcher,
        CityManager $cityManager
    ) {
        $this->session = $session;
        $this->encoder = $encoder;
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;
        $this->cityManager = $cityManager;
    }

    /**
     * {@inheritDoc}
     */
    public function findUserByToken($token)
    {
        return $this->storage->findUserByToken($token);
    }

    /**
     * {@inheritDoc}
     */
    public function findUserBy(array $criteria)
    {
        return $this->storage->findUserBy($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function rememberUser($user)
    {
        $this->session->start();
        $this->session->set($this->schemaId, $user);
        $this->user = $user;
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveUserId()
    {
        if ($this->user === null) {
            $this->session->start();
            $user = $this->session->get($this->schemaId);
            if ($user) {
                $this->user = $user;
            }
        }

        return isset($this->user['id']) ? $this->user['id'] : null;
    }

    /**
     * @return array
     */
    function getActiveUser()
    {
        if(!$this->getActiveUserId()){
            return null;
        }else{
            if(!isset($this->user['couponsCount'])){ //это поле должно быть у всех в сессии, но у пользователей, которые уже имеют сессии на сайте его нет. (SEO)
                $this->user['couponsCount'] = 0;
            }
            return $this->user;
        }
    }


    /**
     * {@inheritDoc}
     */
    public function logout()
    {
        $this->session->clear();
        $this->user = null;
        setcookie ('authautologin', "", time() - 3600,'/','old.boombate.com');
        setcookie ('session', "", time() - 3600,'/','boombate.com');
        setcookie ('session', "", time() - 3600,'/','old.boombate.com');
        setcookie ('session', "", time() - 3600,'/','.boombate.com');
    }

    /**
     * {@inheritDoc}
     */
    public function loginByCredentials($login, $password)
    {
        $user = $this->storage->findUserBy(['username' => $login]);

        if (!$user) {
            throw new AccessDeniedHttpException('Логин или пароль не совпадают');
        }

        if (!$this->isPasswordValid($user['password'], $password)) {
            throw new AccessDeniedHttpException('Логин или пароль не совпадают');
        }

        $this->dispatcher->dispatch(SecurityInterface::LOGIN_EVENT, new LoginEvent($user));

        return $user;
    }

    public function loginByUsername($username)
    {
        $user = $this->storage->findUserBy(['username' => $username]);

        if (!$user) {
            throw new AccessDeniedHttpException('Пользователь не найден');
        }
        $this->dispatcher->dispatch(SecurityInterface::LOGIN_EVENT, new LoginEvent($user));

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function loginByToken($token)
    {
        $user = $this->findUserByToken($token);

        if (!$user) {
            throw new AccessDeniedHttpException('Пользователь не найден');
        }
        $this->dispatcher->dispatch(SecurityInterface::LOGIN_EVENT, new LoginEvent($user));

        return $user;
    }

    public function loginByResetToken($token)
    {
        $user = $this->findUserBy(['reset_token' => $token]);

        if (!$user) {
            throw new AccessDeniedHttpException('Пользователь не найден');
        }
        $this->dispatcher->dispatch(SecurityInterface::LOGIN_EVENT, new LoginEvent($user));

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function createToken($userAgent, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getActiveUserId();
        }

        $name = 'authautologin';

        $token     = sha1(uniqid(mt_rand(), true));

        $userAgent = sha1($userAgent);

        $value = $this->salt($name, $token).'~'.$token;
        $expires   = time() + 31536000;

        $tokenData = [
            'user_id'    => $userId,
            'user_agent' => $userAgent,
            'token'      => $token,
            'expires'    => $expires
        ];

        $this->storage->saveToken($tokenData);

        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'localhost'){
            $domain = false;
        } else {
            $domain = '.' . isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
            //$domain = '.boombate.com';
        }



        return new Cookie('authautologin', $value, $expires, '/', $domain, false, false);
        //return setcookie($name, $value, $expiration, Cookie::$path, Cookie::$domain, Cookie::$secure, Cookie::$httponly);
    }

    private static function salt($name, $value)
    {
        $saltPattern = '123452654123...';
        //print_r(sha1($name.$value.$saltPattern)); die;
        return sha1($name.$value.$saltPattern);
    }


    private function decodeSalt($key,$cookie)
    {
        // Find the position of the split between salt and contents
        $split = strlen($this->salt($key, NULL));

        if (isset($cookie[$split]) AND $cookie[$split] === '~')
        {
            // Separate the salt and the value
            list ($hash, $value) = explode('~', $cookie, 2);

            if ($this->salt($key, $value) === $hash)
            {
                // Cookie signature is valid
                return $value;
            }
        }
        return null;
    }




    /**
     * {@inheritDoc}
     */
    public function forgetToken($token)
    {
        $this->storage->deleteToken($token);
    }

    public function getTokenFromCookies(ParameterBag $cookies)
    {
        $key = 'authautologin';
        $cookie = $cookies->get($key);
        $token = $this->decodeSalt($key,$cookie);
        return $token;
    }

    protected function isPasswordValid($originalPassword, $password)
    {
        return $originalPassword === $this->encoder->encode($password, $originalPassword);
    }

    /**
     * {@inheritDoc}
     */
    public function hasRole($queryRole, $userId = null)
    {
        $user = $this->user;

        if (null !== $userId) {
            $user = $this->storage->findUserById($userId);
        }

        if (null !== $user) {
            $roles = $user['roles'];

            foreach ($roles as $role) {
                if ($role['name'] === $queryRole) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isLogged()
    {
        return $this->getActiveUserId() !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function createUser($username, $plainPassword = null, $data = array())
    {
        $encodedPassword = $plainPassword ? $this->encoder->encode($plainPassword) : sha1(mt_rand());

        $data['reset_token'] = md5($username);
        if (!isset($data['city_id']) || $data['city_id'] == 0){
            $data['city_id'] = $this->cityManager->current()['id'];
        }

        return $this->storage->createUser($username, $encodedPassword, $data);
    }

    public function updateUser($data)
    {
        $user = $this->getActiveUser();

        if (null === $user) {
            throw new AccessDeniedHttpException('Пользователь не авторизован');
        }

        if (isset($data['password'])) {
            $newPassword = $data['password'];
            $oldPassword = $user['password'];

            if ($newPassword && $oldPassword) {
                $newPassword = $this->encoder->encode($newPassword, $oldPassword);
            } else {
                $newPassword = null;
            }
        } else {
            $newPassword = null;
        }

        $this->storage->setSubscribeSetting($user['id'], $data);
        $result = $this->storage->updateUser($user['id'], $data, $newPassword);

        $updatedUser = $this->storage->findUserById($user['id']);
        $updatedUser['avatar'] = isset($this->getActiveUser()['avatar']) ? $this->getActiveUser()['avatar'] : '';
        $this->rememberUser($updatedUser);

        return $result;
    }

    public function changePassword($password)
    {
        $user = $this->getActiveUser();

        if (null === $user) {
            throw new AccessDeniedHttpException('Пользователь не авторизован');
        }

        $newPassword = $password;
        $oldPassword = $user['password'];

        if ($newPassword && $oldPassword) {
            $newPassword = $this->encoder->encode($newPassword, $oldPassword);
        } else {
            $newPassword = null;
        }

        $result = $this->storage->changePassword($user['id'], $newPassword);

//        $updatedUser = $this->storage->findUserById($user['id']);
//        $this->rememberUser($updatedUser);
        return $result;
    }

    public function activateUser($userId)
    {
        return $this->storage->activateUser($userId);
    }

    public function getResetTokenByEmail($email)
    {
        return $this->storage->getResetTokenByEmail($email);
    }

    public function getSocialNetworkAssociations()
    {
        $associations = [];

        $user = $this->getActiveUser();

        if (isset($user['id'])) {
            return $this->storage->getSocialNetworkAssociations($user['id']);
        }

        return $associations;
    }

    public function getUserByOAuthUid($providerName, $uid)
    {
        return $this->storage->getUserByOAuthUid($providerName, $uid);
    }

    public function unassociateSocialNetwork($providerName)
    {
        $user       = $this->getActiveUser();
        $providerId = OAuthProviderTypeEnum::getId($providerName);

        if (! isset($user['id'])) {
            throw new \Exception('You must be logged in');
        }

        $this->storage->unassociateSocialNetwork($providerId, $user['id']);
    }

    public function changeEmail($email)
    {
        $currentUser = $this->getActiveUser();

        if ($this->storage->changeEmail($email, $currentUser['id'])) {
            $currentUser['email'] = $email;
            $currentUser['email_confirm'] = 1;
            $this->activateUser($currentUser['id']);
            $this->rememberUser($currentUser);
        }
    }

    public function isUserSubscribed () {
        $currentUser = $this->getActiveUser();

        return $this->storage->isUserSubscribed($currentUser['id']);
    }

    public function activateNumber ($phone) {
        $currentUser = $this->getActiveUser();

        if ($this->storage->activateNumber($phone, $currentUser['id'])) {
            $currentUser['telephone'] = $phone;
            $currentUser['is_phone_activated'] = 1;
            $this->activateUser($currentUser['id']);
            $this->rememberUser($currentUser);
        };
    }

    public function getCouponCountForUser($userId)
    {
        return $this->storage->getCouponCountForUser($userId);
    }

    public function getReviewCountForUser($userId)
    {
        return $this->storage->getReviewCountForUser($userId);
    }
}
