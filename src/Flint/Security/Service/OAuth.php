<?php
namespace Flint\Security\Service;

use Flint\Security\Service\OAuth\OAuthUserResultInterface;
use OAuth\Common\Exception\Exception as OAuthException;
use OAuth\Common\Service\ServiceInterface as OAuthCommonServiceInterface;
use OAuth\Common\Consumer\Credentials;
use OAuth\OAuth1\Service\AbstractService as OAuth1ServiceInterface;
use OAuth\OAuth2\Service\AbstractService as OAuth2ServiceInterface;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Uri\Uri as OAuthUri;

use Flint\Security\OAuthProviderTypeEnum;
use Flint\Security\Service\Security;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Flint\Security\OAuthtokenStorageAdapterInterface;

class OAuth
{
    protected $config;
    protected $oauthServiceFactory;
    protected $security;
    protected $storage;
    protected $mongo;
    protected $session;

    /**
     * @param callable $factory See $app['flint.security.oauth.service']
     * @param array    $config
     * @param Security $security
     * @param \MongoDB $mongo
     * @param SessionInterface $session
     * @param OAuthtokenStorageAdapterInterface $storage
     */
    public function __construct(
        $factory,
        array $config,
        Security $security,
        \MongoDB $mongo,
        SessionInterface $session,
        OAuthtokenStorageAdapterInterface $storage
    ) {
        $this->oauthServiceFactory = $factory;

        $this->mongo    = $mongo;
        $this->config   = $config;
        $this->security = $security;
        $this->storage  = $storage;
        $this->session  = $session;
    }

    /**
     * Get Authorization URI for given auth provider
     *
     * @param string $provider
     * @param string $callbackUrl
     *
     * @throws OAuthException If OAuth service is not instance of OAuth2ServiceInterface or OAuth1ServiceInterface
     *
     * @return string
     */
    public function getAuthorizationUri($provider, $callbackUrl)
    {
        /** @var $oauthService OAuthCommonServiceInterface */
        $oauthService = $this->getAuthService($provider, $callbackUrl);

        // We check instanceof OAuth2 first, cause it use more frequently than OAuth1
        if ($oauthService instanceof OAuth2ServiceInterface) {
            /** @var $oauthService OAuth2ServiceInterface */
            $url = $oauthService->getAuthorizationUri();
        } else if ($oauthService instanceof OAuth1ServiceInterface) {
            /** @var $oauthService OAuth1ServiceInterface */
            /** @var $token StdOAuth1Token */
            // extra request needed for oauth1 to request a request token :-)
            $token = $oauthService->requestRequestToken();
            /** @var $url OAuthUri */
            $url = $oauthService->getAuthorizationUri(['oauth_token' => $token->getRequestToken()]);
        } else {
            throw new OAuthException("Invalid OAuth Service");
        }

        return $url;
    }

    /**
     * Authorize user on OAuth provider
     *
     * @param string      $provider
     * @param string      $callbackUrl
     * @param string      $oauthToken
     * @param string|null $oauthVerifier
     *
     * @throws OAuthException            If OAuth service is not instance of OAuth2ServiceInterface or OAuth1ServiceInterface
     * @throws \InvalidArgumentException OAuth Verifier is NULL
     * @throws \Exception                If User is NULL
     *
     * @return boolean TRUE if auth succeed and email exists, FALSE eslwere
     */
    public function authorize($provider, $callbackUrl, $oauthToken, $oauthVerifier = null)
    {
        $oauthService = $this->getAuthService($provider, $callbackUrl);

        if (!isset($this->config[$provider]['path'])) {
            throw new \InvalidArgumentException(sprintf("Invalid OAuth configuration for %s provider", $provider));
        }

        $path = $this->config[$provider]['path'];

        // We check instanceof OAuth2 first, cause it use more frequently than OAuth1
        if ($oauthService instanceof OAuth2ServiceInterface) {
            /** @var $oauthService OAuth2ServiceInterface | OAuthUserResultInterface */
            // This was a callback request from google, get the token
            $oauthService->requestAccessToken($oauthToken);

            // Send a request with it
            $result = $oauthService->requestUserInfo($path);
            //$result = json_decode($oauthService->request($path), true);
        } else if ($oauthService instanceof OAuth1ServiceInterface) {
            if(null === $oauthVerifier) {
                throw new \InvalidArgumentException("Verifier for OAuth1 can not be NULL");
            }
            /** @var $oauthService OAuth1ServiceInterface | OAuthUserResultInterface */
            /** @var $token StdOAuth1Token */
            $token = $oauthService->getStorage()->retrieveAccessToken($oauthService->service());
            // This was a callback request from twitter, get the token
            $oauthService->requestAccessToken($oauthToken, $oauthVerifier, $token->getRequestTokenSecret());
            // Send a request now that we have access token
            $result = $oauthService->requestUserInfo($path);
        } else {
            throw new OAuthException("Invalid OAuth Service");
        }

        $uid = $oauthService->getResultUid($result);

        $existingToken = $this->findOAuthToken($uid, OAuthProviderTypeEnum::getId($provider));
        if ($existingToken) {
            // User was authorized before
            $userId = $existingToken['user_id'];
            $user   = $this->security->findUserBy(['id' => $existingToken['user_id']]);

            if (!$user) {
                throw new \Exception(sprintf("Token for user id=%s was found, but User itself is not", $userId));
            }

            $this->security->loginByUsername($user['email']);
        } else {
            $user  = $this->security->getActiveUser();
            $email = isset($user['email']) ? $user['email'] : $oauthService->getResultEmail($result);

            if ($email) {
                // TODO need to set users name from oAuth
                return $this->loginUser($uid, $email, $oauthToken, $provider, $oauthService->getUserData());
            } else {
                $this->saveOAuthData($uid, $oauthToken, $provider, $oauthService->getUserData());

                return false;
            }
        }

        return true;
    }

    /**
     * Get Authorization URI for given auth provider
     *
     * @param string $provider
     * @param string $callbackUrl
     *
     * @throws \InvalidArgumentException If corresponding configuration not found for given provider
     * @throws \InvalidArgumentException If OAuth service factory is not callable
     *
     * @return OAuthCommonServiceInterface | OAuthUserResultInterface
     */
    protected function getAuthService($provider, $callbackUrl)
    {
        if (!(isset($this->config[$provider]['key']) && isset($this->config[$provider]['key']))) {
            throw new \InvalidArgumentException(sprintf("Invalid OAuth configuration for %s provider", $provider));
        }

        if (!is_callable($this->oauthServiceFactory)) {
            throw new \InvalidArgumentException(sprintf(
                "OAuth service factory must be instance of callable, %s given",
                gettype($this->oauthServiceFactory))
            );
        }

        // Setup the credentials for the requests
        $credentials = new Credentials(
            $this->config[$provider]['key'],
            $this->config[$provider]['secret'],
            $callbackUrl
        );

        return call_user_func($this->oauthServiceFactory, $provider, $credentials);
    }

    public function loginUser($uid, $email, $oauthToken, $provider, $userData = [])
    {
        $user = $this->security->findUserBy(['username' => $email]);
        $ret  = ['register' => false];

        if (!$user) {
            // Register new user
            $this->security->createUser($email, null, $userData);
            $ret['register'] = true;
        }

        $user = $this->security->loginByUsername($email);
        $ret['user'] = $user;

        $userId = $this->security->getActiveUserId();
        $secret = isset($this->config[$provider]['secret']) ? $this->config[$provider]['secret'] : '';
        $providerTypeId = OAuthProviderTypeEnum::getId($provider);

        $tokenData = [
            'uid'          => $uid,
            'user_id'      => $userId,
            'access_token' => $oauthToken,
            'secret'       => $secret,
            'auth_type'    => $providerTypeId
        ];

        // If token hasn't been already saved in storage
        if (!$this->findOAuthToken($uid, $providerTypeId)) {
            $this->saveToken($tokenData);
        }

        return $ret;
    }

    /**
     * Save OAuth result data for future authorization
     *
     * @param string $uid
     * @param string $token
     * @param string $provider
     */
    protected function saveOAuthData($uid, $token, $provider, $userData = array())
    {
        $schema = 'oauth';

        $this->session->start();
        $this->session->set($schema, ['uid' => $uid, 'token' => $token, 'provider' => $provider , 'userData' => $userData]);

    }

    /**
     * Get OAuth result data for future authorization
     *
     * @return bool
     */
    public function retrieveOAuthData()
    {
        $schema = 'oauth';

        $this->session->start();
        $data = $this->session->get($schema);

        if(!$data['userData']){
            $data['userData'] = [];
        }

        return $data;
    }

    public function saveToken($tokenData)
    {
        $this->storage->saveToken($tokenData);
    }

    public function findOAuthToken($uid, $providerId)
    {
        return $this->storage->find($uid, $providerId);
    }

    public function checkExistOAuth($provider, $uid)
    {
        $providerId = OAuthProviderTypeEnum::getId($provider);
        return (bool) $this->storage->find($uid, $providerId);
    }


    public function unassociateSocialNetwork($providerName)
    {
        $this->security->unassociateSocialNetwork($providerName);
    }

    public function saveConfirmEmailToken($email, $uid, $provider, $token, $userData = [])
    {
        $this->mongo->selectCollection('oauthEmailConfirmToken')
            ->insert([
                'email'    => $email,
                'uid'      => (string) $uid,
                'provider' => $provider,
                'token'    => $token,
                'userData' => (array) $userData
            ]);
    }

    public function getConfirmEmailToken($email, $uid, $provider, $token)
    {
        return $this->mongo->selectCollection('oauthEmailConfirmToken')
            ->findOne([
                'email'    => $email,
                'uid'      => (string) $uid,
                'provider' => $provider,
                'token'    => $token
            ]);
    }

    public function removeConfirmEmailToken($email, $uid, $provider, $token)
    {
        return $this->mongo->selectCollection('oauthEmailConfirmToken')
            ->remove([
                'email'    => $email,
                'uid'      => (string) $uid,
                'provider' => $provider,
                'token'    => $token
            ]);
    }


}
