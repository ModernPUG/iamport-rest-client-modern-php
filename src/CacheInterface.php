<?php

namespace ModernPUG\Iamport;

interface CacheInterface
{
    /**
     * @return ?string
     */
    public function getAccessToken();

    /**
     * @param string $accessToken
     * @param int $expiresAt timestamp
     */
    public function rememberAccessToken($accessToken, $expiresAt);
}
