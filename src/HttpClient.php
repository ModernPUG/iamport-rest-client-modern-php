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
        return $this->httpAuthCall('GET', $uri);
    }

    public function httpDelete($uri)
    {
        return $this->httpAuthCall('DELETE', $uri);
    }

    public function httpPost($uri, $data = null)
    {
        $data = $data ?: [];

        return $this->httpAuthCall('POST', $uri, ['body' => json_encode($data)]);
    }

    private function httpAuthCall($method, $uri, $options = [])
    {
        $options = $options ?: [];
        $options = array_replace_recursive(['headers' => ['Authorization' => $this->getAccessCode()]], $options);

        return $this->httpJsonCall($method, $uri, $options);
    }

    private function getAccessCode()
    {
        $accessToken = null;

        try {
            $accessToken = $this->cache->getAccessToken();
            if ($accessToken) {
                return $accessToken;
            }

            $body = json_encode(['imp_key' => $this->imp_key, 'imp_secret' => $this->imp_secret]);
            $response = $this->httpJsonCall(
                'POST',
                'https://api.iamport.kr/users/getToken',
                ['body' => $body]
            );
            $response = $response->response;

            $accessToken = $response->access_token;
            $expiresAt = time() + $response->expired_at - $response->now;
            $this->cache->rememberAccessToken($accessToken, $expiresAt);
        } catch (Exception $e) {
            //todo: Exception 관련 처리
            //throw new IamportAuthException('[API 인증오류] '.$e->getMessage(), $e->getCode());
            $accessToken = null;
        }

        return $accessToken;
    }

    private function httpJsonCall($method, $uri, $options = [])
    {
        $options = array_replace_recursive([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ], ($options ?: []));

        $response = $this->httpCall($method, $uri, $options);
        $contents = $response->getBody()->getContents();
        $result = json_decode(trim($contents));

        return $result;
    }

    private function httpCall($method, $uri, $options = [])
    {
        return $this->client->request($method, $uri, ($options ?: []));
    }
}
