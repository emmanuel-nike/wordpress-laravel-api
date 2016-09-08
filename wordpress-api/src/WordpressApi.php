<?php

namespace n1k3\WordpressApi;

use n1k3\WordpressApi\Lib\Util\JsonDecoder;
use n1k3\WordpressApi\Lib\HmacSha1;
use n1k3\WordpressApi\Lib\Client;
use n1k3\WordpressApi\Lib\Token;
use n1k3\WordpressApi\Lib\Response;
use n1k3\WordpressApi\Lib\WordpressOAuthException;
use n1k3\WordpressApi\Lib\Util;
use n1k3\WordpressApi\Lib\Config;
use n1k3\WordpressApi\Lib\Request;
use Session;

/**
 * WordpressOAuth class for interacting with the Wordpress API.
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class WordpressApi extends Config
{
    const API_VERSION = '1.1';
    const API_HOST = 'https://public-api.wordpress.com/';
    const UPLOAD_HOST = 'https://upload.Wordpress.com';
    const UPLOAD_CHUNK = 40960; // 1024 * 40

    private $response;
    private $bearer;
    private $client;
    private $token;
    private $signatureMethod;
    private $callbackUrl;

    /**
     * Constructor
     *
     * @param string|null $oauthToken       The Client Token (optional)
     */
    public function __construct($oauthToken = null)
    {
        $clientID = config('wordpress-api.client_id');
        $clientSecret = config('wordpress-api.client_secret');

        $this->callbackUrl = config('wordpress-api.callback_url');

        $this->resetLastResponse();
        $this->client = new Client($clienID, $clientSecret);

        if (!empty($oauthToken)) {
            $this->token = new Token($oauthToken);
            $this->bearer = $oauthToken;
        }
    }

    /**
     * @param string $oauthToken
     */
    public function setOauthToken($oauthToken)
    {
        $this->token = new Token($oauthToken);
        $this->bearer = $oauthToken;
    }

    /**
     * @return string|null
     */
    public function getLastApiPath()
    {
        return $this->response->getApiPath();
    }

    /**
     * @return int
     */
    public function getLastHttpCode()
    {
        return $this->response->getHttpCode();
    }

    /**
     * @return array
     */
    public function getLastXHeaders()
    {
        return $this->response->getXHeaders();
    }

    /**
     * @return array|object|null
     */
    public function getLastBody()
    {
        return $this->response->getBody();
    }

    /**
     * Resets the last response cache.
     */
    public function resetLastResponse()
    {
        $this->response = new Response();
    }

    /**
     * Build authorize URL.
     *
     * @return string
     */
    public function authorizeUrl()
    {

        $url = $this->url('oauth2/authorize', array('client_id' => $this->client->id, 
                                                    'redirect_uri' => $this->client->callbackUrl, 
                                                    'response_type' => 'code',
                                                    'scope' => 'global'));
        return $url;
    }

    /**
     * Get access tokens and info for user.
     *
     * @return string
     */
    public function accessToken($code)
    {
        $access_token = $this->oauth2("oauth2/token", array( 'client_id' => $this->client->id,
                                                            'redirect_uri' =>$this->client->callbackUrl,
                                                            'client_secret' => $this->client->secret,
                                                            'code' => $code, // The code from the previous request
                                                            'grant_type' => 'authorization_code'));

        return $access_token;
    }

    /**
     * Make URLs for user browser navigation.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return string
     */
    public function url($path, array $parameters)
    {
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $query = http_build_query($parameters);
        return sprintf('%s/%s?%s', self::API_HOST, $path, $query);
    }

    /**
     * Make /oauth2/* requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function oauth2($path, array $parameters = [])
    {
        $method = 'POST';
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $url = sprintf('%s/%s', self::API_HOST, $path);
        $request = Request::fromClientAndToken($this->client, $this->token, $method, $url, $parameters);
        $result = $this->request($request->getNormalizedHttpUrl(), $method, $parameters);
        $response = JsonDecoder::decode($result, $this->decodeJsonAsArray);
        $this->response->setBody($response);
        return $response;
    }

    /**
     * Make GET requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function get($path, array $parameters = [])
    {
        return $this->http('GET', self::API_HOST, $path, $parameters);
    }

    /**
     * Make POST requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function post($path, array $parameters = [])
    {
        return $this->http('POST', self::API_HOST, $path, $parameters);
    }

    /**
     * Make DELETE requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function delete($path, array $parameters = [])
    {
        return $this->http('DELETE', self::API_HOST, $path, $parameters);
    }

    /**
     * Make PUT requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function put($path, array $parameters = [])
    {
        return $this->http('PUT', self::API_HOST, $path, $parameters);
    }
    

    /**
     * @param string $method
     * @param string $host
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function http($method, $host, $path, array $parameters)
    {
        $this->resetLastResponse();
        $url = sprintf('%s/%s/%s.json', $host, self::API_VERSION, $path);
        $this->response->setApiPath($path);
        $result = $this->oAuthRequest($url, $method, $parameters);
        $response = JsonDecoder::decode($result, $this->decodeJsonAsArray);
        $this->response->setBody($response);
        return $response;
    }

    /**
     * Format and sign an OAuth / API request
     *
     * @param string $url
     * @param string $method
     * @param array  $parameters
     *
     * @return string
     * @throws WordpressOAuthException
     */
    private function oAuthRequest($url, $method, array $parameters)
    {
        $request = Request::fromClientAndToken($this->client, $this->token, $method, $url, $parameters);
        $authorization = 'Authorization: Bearer ' . $this->bearer;
        return $this->request($request->getNormalizedHttpUrl(), $method, $authorization, $parameters);
    }

    /**
     * Make an HTTP request
     *
     * @param string $url
     * @param string $method
     * @param string $authorization
     * @param array $postfields
     *
     * @return string
     * @throws WordpressOAuthException
     */
    private function request($url, $method, $authorization = '', $postfields)
    {
        /* Curl settings */
        $options = [
            // CURLOPT_VERBOSE => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectionTimeout,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json', $authorization, 'Expect:'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => $this->userAgent,
        ];
        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy['CURLOPT_PROXY'];
            $options[CURLOPT_PROXYUSERPWD] = $this->proxy['CURLOPT_PROXYUSERPWD'];
            $options[CURLOPT_PROXYPORT] = $this->proxy['CURLOPT_PROXYPORT'];
            $options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        }
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = Util::buildHttpQuery($postfields);
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;
        }
        if (in_array($method, ['GET', 'PUT', 'DELETE']) && !empty($postfields)) {
            $options[CURLOPT_URL] .= '?' . Util::buildHttpQuery($postfields);
        }
        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $options);
        $response = curl_exec($curlHandle);
        // Throw exceptions on cURL errors.
        if (curl_errno($curlHandle) > 0) {
            throw new WordpressOAuthException(curl_error($curlHandle), curl_errno($curlHandle));
        }
        $this->response->setHttpCode(curl_getinfo($curlHandle, CURLINFO_HTTP_CODE));
        $parts = explode("\r\n\r\n", $response);
        $responseBody = array_pop($parts);
        $responseHeader = array_pop($parts);
        $this->response->setHeaders($this->parseHeaders($responseHeader));
        curl_close($curlHandle);
        return $responseBody;
    }

    /**
     * Get the header info to store.
     *
     * @param string $header
     *
     * @return array
     */
    private function parseHeaders($header)
    {
        $headers = [];
        foreach (explode("\r\n", $header) as $line) {
            if (strpos($line, ':') !== false) {
                list ($key, $value) = explode(': ', $line);
                $key = str_replace('-', '_', strtolower($key));
                $headers[$key] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * Encode application authorization header with base64.
     *
     * @param Client $client
     *
     * @return string
     */
    private function encodeAppAuthorization($client)
    {
        // TODO: key and secret should be rfc 1738 encoded
        $id = $client->id;
        $secret = $client->secret;
        return base64_encode($id . ':' . $secret);
    }
}
