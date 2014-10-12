<?php
namespace Flint\Security\Service\OAuth;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;
use Flint\Security\Service\OAuth\OAuth2AbstractService;

class Vkontakte extends OAuth2AbstractService
{
    /**
     * {@inheritDoc}
     */
    public function __construct(
        Credentials $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = [],
        UriInterface $baseApiUri = null
    )
    {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://oauth.vk.com/');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://oauth.vk.com/authorize');
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://oauth.vk.com/oauth/access_token');
    }

    /**
     * {@inheritDoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();


        // [a.chernykh] TODO: Validate data key names
        $token->setAccessToken($data['access_token']);
        $token->setLifeTime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);
        $token->setExtraParams($data);

        return $token;
    }

    protected function modifyRequestPath(&$path)
    {


        //запрашиваем данные пользователя
        $token = $this->getStorage()->retrieveAccessToken($this->service());
        $path .= '?uid=' . $token->getExtraParams()['user_id'];


    }

    public function getResultUid($result)
    {
        return $result['response'][0]['uid'];
    }

    function _getUserData()
    {
        $result = $_SESSION['oauth_user_info'];
        $result = $result['response'][0];
        return array(
            'name' => isset($result['first_name']) ? $result['first_name'] : '',
            'lastname' => isset($result['last_name']) ? $result['last_name'] : ''/*,
            'accountUrl' => isset($result['uid']) ? 'vk.com/id' . $result['uid'] : 'vk.com'*/
        );

    }


}
