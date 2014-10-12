<?php
namespace Flint\Security\Service\Http\Client;

use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\UriInterface;

/**
 * Client implementation for cURL
 */
class CurlClient implements ClientInterface
{
    private $maxRedirects;
    private $timeout;

    /**
     * @param int $maxRedirects Maximum redirects for client
     * @param int $timeout Request timeout time for client in seconds
     */
    public function __construct($maxRedirects = 5, $timeout = 15)
    {
        $this->maxRedirects = $maxRedirects;
        $this->timeout = $timeout;
    }

    /**
     * Any implementing HTTP providers should send a request to the provided endpoint with the parameters.
     * They should return, in string form, the response body and throw an exception on error.
     *
     * @param UriInterface $endpoint
     * @param mixed $requestBody
     * @param array $extraHeaders
     * @param string $method
     * @return string
     * @throws TokenResponseException
     * @throws \InvalidArgumentException
     */
    public function retrieveResponse(UriInterface $endpoint, $requestBody, array $extraHeaders = array(), $method = 'POST')
    {
        // Normalize method name
        $method = strtoupper($method);

        // Normalize headers
        array_walk($extraHeaders,
            function(&$val, &$key)
            {
                $key = ucfirst( strtolower($key) );
                $val = ucfirst( strtolower($key) ) . ': ' . $val;
            }
        );

        $curl = curl_init();

        if( $method === 'GET' && !empty($requestBody) ) {
            throw new \InvalidArgumentException('No body expected for "GET" request.');
        }

        if(!isset($extraHeaders['Content-type'])
            && $method === 'POST'
            && is_array($requestBody)
        ) {
            $requestBody = http_build_query($requestBody);
            $extraHeaders['Content-type'] = 'Content-type: application/x-www-form-urlencoded';

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        }

        $extraHeaders['Host']       = 'Host: ' . $endpoint->getHost();
        $extraHeaders['Connection'] = 'Connection: close';

        $level = error_reporting(0);
        $url   = $endpoint->getAbsoluteUri();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_MAXREDIRS, $this->maxRedirects);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $extraHeaders);

        $response = curl_exec($curl);

        curl_close($curl);
        error_reporting($level);

        if(false === $response) {
            $lastError = error_get_last();
            throw new TokenResponseException($lastError['message']);
        }

        return $response;
    }
}
