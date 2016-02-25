<?php

namespace ModernPUG\Iamport;

use Exception;
use GuzzleHttp\Client as Guzzle;

class IamportHttpClient
{
    private $imp_key = null;
    private $imp_secret = null;

    private $access_token = null;
    private $expired_at = null;
    private $now = null;

    private $client;

    public function __construct($imp_key, $imp_secret, Guzzle $guzzle = null)
    {
        $this->imp_key = $imp_key;
        $this->imp_secret = $imp_secret;
        $this->client = $guzzle ?: new Guzzle();
    }

    public function httpGet($uri)
    {
        return $this->httpAuthCall('GET', $uri);
    }

    public function httpPost($uri, $data = null)
    {
        return $this->httpAuthCall('POST', $uri, [
            'body' => json_encode($data ?: []),
        ]);
    }

    public function httpDelete($uri)
    {
        return $this->httpAuthCall('DELETE', $uri);
    }

    private function httpAuthCall($method, $uri, $options = [])
    {
        $options = array_replace_recursive([
            'headers' => [
                'Authorization' => $this->getAccessCode(),
            ],
        ], ($options ?: []));

        return $this->httpJsonCall($method, $uri, $options);
    }

    private function getAccessCode()
    {
        try {
            $now = time();
            if ($now < $this->expired_at && !empty($this->access_token)) {
                return $this->access_token;
            }
            $this->expired_at = null;
            $this->access_token = null;

            $response = $this->httpJsonCall(
                'POST', 'https://api.iamport.kr/users/getToken', [
                    'body' => json_encode([
                        'imp_key' => $this->imp_key,
                        'imp_secret' => $this->imp_secret,
                    ]),
                ]
            )->response;

            $offset = $response->expired_at - $response->now;
            $this->expired_at = time() + $offset;
            $this->access_token = $response->access_token;

            return $response->access_token;
        } catch (Exception $e) {
            throw new IamportAuthException('[API 인증오류] '.$e->getMessage(), $e->getCode());
        }
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