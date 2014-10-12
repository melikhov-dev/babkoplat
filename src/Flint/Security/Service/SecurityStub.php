<?php
namespace Flint\Security\Service;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Cookie;

use Flint\Security\SecurityInterface;

class SecurityStub implements SecurityInterface
{
    /**
     * @var array
     */
    protected $user;

    public function __construct() {
        $this->user = [
            'id' => 1
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function findUserByToken($token)
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function findUserBy(array $criteria)
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function rememberUser($user)
    {
        // do nothing
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveUserId()
    {
        return $this->user['id'];
    }

    /**
     * {@inheritDoc}
     */
    public function logout()
    {
            // do nothing
    }

    /**
     * {@inheritDoc}
     */
    public function loginByCredentials($login, $password)
    {
        return $this->user;
    }

    public function loginByUsername($username)
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function loginByToken($token)
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function createToken($userAgent, $userId = null)
    {
        if (null === $userId) {
            $userId = $this->getActiveUserId();
        }

        $token     = sha1(uniqid(mt_rand(), true));
        $userAgent = sha1($userAgent);
        $expires   = time() + 31536000;

        return new Cookie('authautologin', $userAgent.'~'.$token, $expires, '/', '.boombate.com', false, false);
    }

    /**
     * {@inheritDoc}
     */
    public function forgetToken($token)
    {
        // do nothing
    }

    public function getTokenFromCookies(ParameterBag $cookies)
    {
        $cookie = $cookies->get('authautologin');
        $token  = null;

        if ($cookie) {
            $token = explode('~', $cookie)[1];
        }

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    public function hasRole($queryRole, $userId = null)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isLogged()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function createUser($username, $plainPassword = null)
    {
        // do nothing
    }

    function getActiveUser()
    {
        return null;
    }
}
