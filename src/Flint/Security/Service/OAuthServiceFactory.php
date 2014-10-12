<?php
namespace Flint\Security\Service;

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\OAuth1\Signature\Signature;
use OAuth\Common\Exception\Exception as OAuthException;
use OAuth\Common\Http\Client\ClientInterface;

use OAuth\OAuth1\Service\AbstractService as OAuth1ServiceInterface;
use OAuth\OAuth2\Service\AbstractService as OAuth2ServiceInterface;

class OAuthServiceFactory
{
    /**
     * @var ClientInterface
     */
    private $httpClient;
    protected $serviceMap = [];

    public function __construct(ClientInterface $httpClient, $serviceMap)
    {
        $this->httpClient = $httpClient;
        $this->serviceMap = $serviceMap;
    }

    /**
     * @param string                $serviceName
     * @param Credentials           $credentials
     * @param TokenStorageInterface $storage
     * @param array                 $scopes
     *
     * @return null
     *
     * @throws OAuthException
     * @throws \InvalidArgumentException
     */
    public function createService($serviceName, Credentials $credentials, TokenStorageInterface $storage, $scopes = [])
    {
        if (!isset($this->serviceMap[$serviceName])) {
            throw new \InvalidArgumentException(sprintf("Service class for provider %s was not found", $serviceName));
        }

        $serviceClass = $this->serviceMap[$serviceName];

        if(!class_exists($serviceClass)) {
            throw new \InvalidArgumentException(sprintf("Class %s doesn't exists", $serviceClass));
        }

        $reflectionClass = new \ReflectionClass($serviceClass);

        // if an oauth2 version exists, prefer it
        if($reflectionClass->isSubclassOf('\\OAuth\\OAuth2\\Service\\AbstractService')) {
            // resolve scopes
            $resolvedScopes  = [];
            $constants       = $reflectionClass->getConstants();

            foreach($scopes as $scope)
            {
                $key = strtoupper('SCOPE_' . $scope);
                // try to find a class constant with this name
                if( array_key_exists( $key, $constants ) ) {
                    $resolvedScopes[] = $constants[$key];
                } else {
                    $resolvedScopes[] = $scope;
                }
            }

            return new $serviceClass($credentials, $this->httpClient, $storage, $resolvedScopes);
        } else if ($reflectionClass->isSubclassOf('\\OAuth\\OAuth1\\Service\\AbstractService')) {
            if(!empty($scopes)) {
                throw new OAuthException('Scopes passed to ServiceFactory::createService but an OAuth1 service was requested.');
            }
            $signature = new Signature($credentials);

            return new $serviceClass($credentials, $this->httpClient, $storage, $signature);
        } else {
            throw new OAuthException(sprintf("Class %s is not instance of valid OAuth service", $serviceClass));
        }
    }
}
