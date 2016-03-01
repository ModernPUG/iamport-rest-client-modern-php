<?php

namespace ModernPUG\Iamport;

use Exception;
use GuzzleHttp\Client as Guzzle;

class HttpClient
{
    private $imp_key = null;
    private $imp_secret = null;

    private $cache;
    private $client;

    public function __construct($imp_key, $imp_secret, CacheInterface $cache, Guzzle $client = null)
    {
        $this->imp_key = $imp_key;
        $this->imp_secret = $imp_secret;

        $this->cache = $cache;
        $this->client = $client ?: new Guzzle();
    }

    public function httpGet($uri)
    {
        return $this->authJsonRequest('GET', $uri);
    }

    public function httpDelete($uri)
    {
        return $this->authJsonRequest('DELETE', $uri);
    }

    public function httpPost($uri, array $data = [])
    {
        return $this->authJsonRequest('POST', $uri, ['json' => $data]);
    }

    private function authJsonRequest($method, $uri, array $options = [])
    {
        try {
            $options = array_replace_recursive(['headers' => ['Authorization' => $this->getAccessCode()]], $options);
        } catch (Exception $e) {
            //TODO: Exception 관련 처리
            // 인증 실패 처리
        }
        return $this->jsonRequest($method, $uri, $options);
    }

    private function getAccessCode()
    {
        $accessToken = null;

        $accessToken = $this->cache->getAccessToken();
        if ($accessToken) {
            return $accessToken;
        }

        $response = $this->jsonRequest(
            'POST',
            'https://api.iamport.kr/users/getToken',
            [
                'json' =>
                    [
                        'imp_key' => $this->imp_key,
                        'imp_secret' => $this->imp_secret
                    ]
            ]
        );

        $expiresAt = time() + $response->expired_at - $response->now;
        $accessToken = $response->access_token;
        $this->cache->rememberAccessToken($accessToken, $expiresAt);
        return $accessToken;
    }

    private function jsonRequest($method, $uri, array $options = [])
    {
        $options = $this->jsonOption($options);
        $response = $this->request($method, $uri, $options);
        $contents = $response->getBody()->getContents();
        $result = json_decode(trim($contents));
        return $this->handleResponse($result);
    }

    private function request($method, $uri, array $options = [])
    {
        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param array $options
     * @return array
     */
    private function jsonOption(array $options)
    {
        return array_replace_recursive([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ], $options);
    }

    /**
     * @param $response
     * @return mixed
     * @throws Exception
     */
    private function handleResponse($response)
    {
        if ($response->code != 0) {
            // has something problem, see the message
            // TODO: wrap Custom Exception?
            throw new Exception($response->message, $response->code);
        }
        // or ? OK
        return $response->response;
    }
}
