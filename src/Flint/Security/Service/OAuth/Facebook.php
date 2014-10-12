<?php
namespace Flint\Security\Service\OAuth;

use Flint\Security\Service\OAuth\OAuth2AbstractService;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class Facebook extends OAuth2AbstractService
{
    public function __construct(Credentials $credentials, ClientInterface $httpClient, TokenStorageInterface $storage, $scopes = array(), UriInterface $baseApiUri = null)
    {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);
        if( null === $baseApiUri ) {
            $this->baseApiUri = new Uri('https://graph.facebook.com/');
        }
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://www.facebook.com/dialog/oauth');
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://graph.facebook.com/oauth/access_token');
    }

    /**
     * @param string $responseBody
     * @return \OAuth\Common\Token\TokenInterface|\OAuth\OAuth2\Token\StdOAuth2Token
     * @throws \OAuth\Common\Http\Exception\TokenResponseException
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        // Facebook gives us a query string ... Oh wait. JSON is too simple, understand ?
        parse_str($responseBody, $data);
        if( null === $data || !is_array($data) ) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif( isset($data['error'] ) ) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();

        $token->setAccessToken( $data['access_token'] );
        $token->setLifeTime( $data['expires'] );

        if( isset($data['refresh_token'] ) ) {
            $token->setRefreshToken( $data['refresh_token'] );
            unset($data['refresh_token']);
        }

        unset( $data['access_token'] );
        unset( $data['expires'] );
        $token->setExtraParams( $data );

        return $token;
    }

    function _getUserData()
    {
        $result = $_SESSION['oauth_user_info'];
        $data =  array(
            'name'       => isset($result['first_name']) ? $result['first_name'] : '',
            'lastname'   => isset($result['last_name']) ? $result['last_name'] : ''/*,
            'accountUrl' => isset($result['username']) ? 'facebook.com/' . $result['username'] : 'facebook.com'*/
        );

        if(isset($result['gender']) && $result['gender'] == 'male'){
            $data['sex'] = 1;
        }
        return $data;

    }
}
