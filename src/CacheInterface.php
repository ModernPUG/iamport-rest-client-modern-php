<?php

namespace ModernPUG\Iamport;

interface CacheInterface
{
    public function getAccessToken();
    public function rememberAccessToken($accessToken, $expiresAt);
}
