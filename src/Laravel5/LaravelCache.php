<?php

namespace ModernPUG\Iamport\Laravel5;

use Cache;
use ModernPUG\Iamport\CacheInterface;
use ModernPUG\Iamport\Cache as StaticCache;

class LaravelCache implements CacheInterface
{
    private $staticCache = null;

    public function __construct()
    {
        $this->staticCache = new StaticCache();
    }

    public function getAccessToken()
    {
        $accessToken = $this->staticCache->getAccessToken();
        if ($accessToken) {
            return $accessToken;
        }

        if (Cache::has('access-token-info')) {
            $info = json_decode(Cache::get('access-token-info'));
            $accessToken = $info->accessToken;
            $expiresAt = $info->expiresAt;

            if ($info->expiresAt < time()) {
                // LaravelCache 에 캐시되어 있어도 시간 정보를 얻어 만료됐으면 강제 만료시킨다
                // LaravelCache 는 minutes 단위로 캐시 만료를 지정하기 때문에 정확하지 않아서 이렇게 사용 함
                Cache::forget('access-token-info');
                return null;
            }

            // 같은 Request 에서 여러번 API 호출을 하는 경우를 위해 staticCache 에 캐싱한다
            $this->staticCache->rememberAccessToken($accessToken, $expiresAt);

            return $accessToken;
        }

        return null;
    }

    public function rememberAccessToken($accessToken, $expiresAt)
    {
        $this->staticCache->rememberAccessToken($accessToken, $expiresAt);

        Cache::forever('access-token-info', json_encode([
            'accessToken' => $accessToken,
            'expiresAt' => $expiresAt,
        ]));
    }
}
