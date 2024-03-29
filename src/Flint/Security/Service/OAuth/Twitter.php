<?php
namespace Flint\Security\Service\OAuth;

use OAuth\OAuth1\Signature\SignatureInterface;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\ClientInterface;

class Twitter extends OAuth1AbstractService
{
    public function __construct(Credentials $credentials, ClientInterface $httpClient, TokenStorageInterface $storage, SignatureInterface $signature, UriInterface $baseApiUri = null)
    {
        parent::__construct($credentials, $httpClient, $storage, $signature, $baseApiUri);
        if( null === $baseApiUri ) {
            $this->baseApiUri = new Uri('https://api.twitter.com/1.1/');
        }
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getRequestTokenEndpoint()
    {
        return new Uri('https://api.twitter.com/oauth/request_token');
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getAuthorizationEndpoint()
    {
        // @todo if the app will post tweets, authorize must be used instead.
        // figure something out re: that but it's late and i don't want to now
        return new Uri('https://api.twitter.com/oauth/authenticate');
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://api.twitter.com/oauth/access_token');
    }

    /**
     * We need a separate request token parser only to verify the `oauth_callback_confirmed` parameter. For the actual
     * parsing we can just use the default access token parser.
     *
     * @param string $responseBody
     * @return \OAuth\Common\Token\TokenInterface|\OAuth\OAuth1\Token\StdOAuth1Token
     * @throws \OAuth\Common\Http\Exception\TokenResponseException
     */
    protected function parseRequestTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        if( null === $data || !is_array($data) ) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (!isset($data['oauth_callback_confirmed']) || $data['oauth_callback_confirmed'] != 'true') {
            throw new TokenResponseException('Error in retrieving token.');
        }

        return $this->parseAccessTokenResponse($responseBody);
    }

    /**
     * @param string $responseBody
     * @return \OAuth\Common\Token\TokenInterface|\OAuth\OAuth1\Token\StdOAuth1Token
     * @throws \OAuth\Common\Http\Exception\TokenResponseException
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        if( null === $data || !is_array($data) ) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif( isset($data['error'] ) ) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth1Token();

        $token->setRequestToken( $data['oauth_token'] );
        $token->setRequestTokenSecret( $data['oauth_token_secret'] );
        $token->setAccessToken( $data['oauth_token'] );
        $token->setAccessTokenSecret( $data['oauth_token_secret'] );

        $token->setEndOfLife(StdOAuth1Token::EOL_NEVER_EXPIRES);
        unset( $data['oauth_token'], $data['oauth_token_secret'] );
        $token->setExtraParams( $data );

        return $token;
    }

    function _getUserData()
    {
        $result = $_SESSION['oauth_user_info'];

        $data =  array(
            'name'       => isset($result['name']) ? $result['name'] : '',
            'lastname'   => isset($result['last_name']) ? $result['last_name'] : ''/*,
            'accountUrl' => isset($result['screen_name']) ? 'twitter.com/' . $result['screen_name'] : 'twitter.com'*/
        );

        return $data;

    }
}
