<?php

namespace ModernPUG\Iamport;

class Cache implements CacheInterface
{
    private $expiresAt = null;
    private $accessToken = null;

    /**
     * {@inheritdoc}
     */
    public function getAccessToken()
    {
        $now = time();
        if ($now < $this->expiresAt && !empty($this->accessToken)) {
            return $this->accessToken;
        }
        $this->expiresAt = null;
        $this->accessToken = null;

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function rememberAccessToken($accessToken, $expiresAt)
    {
        $this->accessToken = $accessToken;
        $this->expiresAt = $expiresAt;
    }
}
