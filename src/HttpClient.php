<?php

namespace ModernPUG\Iamport;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class HttpClient
{
    /** @var \ModernPUG\Iamport\Configuration */
    protected $config;
    
    /** @var \ModernPUG\Iamport\CacheInterface */
    private $cache;
    
    /** @var \GuzzleHttp\Client */
    private $client;
    
    public function __construct(Configuration $config, CacheInterface $cache = null, Guzzle $client = null)
    {
        $this->config = $config;
        $this->cache = $cache ?: new Cache();
        $this->client = $client ?: new Guzzle();
    }

    /**
     * @param string $uri
     * @return array
     */
    public function httpGet($uri)
    {
        return $this->requestWithAuth('GET', $uri);
    }

    /**
     * @param string $uri
     * @return array
     */
    public function httpDelete($uri)
    {
        return $this->requestWithAuth('DELETE', $uri);
    }

    /**
     * @param string $uri
     * @param array $formData
     * @return array
     */
    public function httpPost($uri, array $formData = [])
    {
        return $this->requestWithAuth('POST', $uri, $formData);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $formData
     * @return array
     */
    private function requestWithAuth($method, $uri, array $formData = [])
    {
        try {
            $authToken = $this->getAuthToken();
        } catch (\Exception $e) {
            // TODO 상세 처리 필요
            throw new Exception\AuthException($e->getMessage(), $e->getCode(), $e);
        }
        return $this->requestViaJson($method, $uri, $formData, [
            'Authorization' => $authToken,
        ]);
    }

    /**
     * @return ?string
     */
    public function getAuthToken()
    {
        $accessToken = $this->cache->getAccessToken();
        if ($accessToken) {
            return $accessToken;
        }

        $response = $this->requestViaJson('POST', "/users/getToken", [
            'imp_key' => $this->config->getImpKey(),
            'imp_secret' => $this->config->getImpSecret(),
        ]);

        $expiresAt = time() + $response['expired_at'] - $response['now'];
        $accessToken = $response['access_token'];
        $this->cache->rememberAccessToken($accessToken, $expiresAt);
        return $accessToken;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $formData
     * @param array $headers
     * @return array
     */
    protected function requestViaJson($method, $uri, array $formData = [], array $headers = [])
    {
        try {
            $response = $this->client->request($method, "{$this->config->getHost()}{$uri}", [
                'headers' => $headers + [
                    'Content-Type' => 'application/json',
                ],
                'json' => $formData
            ]);
        } catch (ClientException $e) {
            $body = $this->getContentsFromResponse($e->getResponse());
            $code = isset($body['code']) ? $body['code'] : 0;
            $message = isset($body['message']) ? $body['message'] : '알 수 없는 에러가 발생하였습니다.';
            throw new Exception\RuntimeException($message, $code);
        }
        $result = $this->getContentsFromResponse($response);
        if ($result['code'] != 0) {
            throw new Exception\RuntimeException($result['message'], $result['code']);
        }
        return $result['response'];
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    private function getContentsFromResponse(ResponseInterface $response = null)
    {
        if (!$response) return [];
        $body = $response->getBody();
        if (!$body) return [];
        $contents = $body->__toString(); // getContents는 fp에 따라서 부분값이 나올 여지가 있습니다.
        if (!$contents) return [];
        return @json_decode(trim($contents), true) ?: [];
    }
}
