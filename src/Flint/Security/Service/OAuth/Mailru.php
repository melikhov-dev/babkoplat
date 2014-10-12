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

class Mailru extends OAuth2AbstractService
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
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        if( null === $baseApiUri ) {
            $this->baseApiUri = new Uri('https://connect.mail.ru/');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://connect.mail.ru/oauth/authorize');
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://connect.mail.ru/oauth/token');
    }

    /**
     * {@inheritDoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {

        $data = json_decode($responseBody,true);

        if(null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif(isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();


        // [a.chernykh] TODO: Validate data key names
        $token->setAccessToken($data['access_token']);
        $token->setLifeTime($data['expires_in']);

        if( isset($data['refresh_token'])) {
            $token->setRefreshToken( $data['refresh_token'] );
            unset($data['refresh_token']);
        }

        unset( $data['access_token']);
        unset( $data['expires_in']);
        $token->setExtraParams($data);

        return $token;
    }

    protected function modifyRequestPath(&$path)
    {
        //запрашиваем данные пользователя
        $token = $this->getStorage()->retrieveAccessToken($this->service())->getAccessToken();

        $data = [];
        $data['method'] = 'users.getInfo';
        $data['secure'] = '1';
        $data['app_id'] = $this->credentials->getConsumerId();
        $data['session_key'] = $token;

        $sign = md5("app_id=".$this->credentials->getConsumerId()."method=users.getInfosecure=1session_key=".$token.$this->credentials->getConsumerSecret());

        $data['sig'] = $sign;

        $path .= '?';
        foreach($data as $key=>$value){
            $path .= $key.'='.$value.'&';
        }
            

    }

    protected function modifyRequestUri(Uri &$uri)
    {
        if($uri->getPath() == '/platform/api'){
            //запрашиваем данные пользователя
            $token = $this->getStorage()->retrieveAccessToken($this->service())->getAccessToken();
            $uri->addToQuery('method','users.getInfo');
            $uri->addToQuery('secure','1');
            $uri->addToQuery('app_id',$this->credentials->getConsumerId());
            $uri->addToQuery('session_key',$token);
            $sign = md5("app_id=".$this->credentials->getConsumerId()."method=users.getInfosecure=1session_key=".$token.$this->credentials->getConsumerSecret());
            $uri->addToQuery('sig',$sign);
        }
    }

    public function getResultUid($result)
    {
        return $result[0]['uid'];
    }

    public function getResultEmail($result)
    {
        return $result[0]['email'];
    }

    function _getUserData()
    {
        $result = $_SESSION['oauth_user_info'][0];

        $data =  array(
            'name'       => isset($result['nick']) ? $result['nick'] : '',
            'lastname'   => isset($result['last_name']) ? $result['last_name'] : ''/*,
            'accountUrl' => isset($result['email'])
                ? 'my.mail.ru/mail/' . explode('@', $result['email'])[0]
                : 'my.mail.ru'*/
        );

        if(isset($result['sex']) && $result['sex'] == 0){
            $data['sex'] = 1;
        }
        return $data;

    }


}
