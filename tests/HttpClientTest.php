<?php
namespace ModernPUG\Iamport;

use ModernPUG\Iamport\Exception\RuntimeException;
use PHPUnit_Framework_TestCase;

class HttpClientTest extends PHPUnit_Framework_TestCase
{
    // 다음은 아임포트에서 기본 제공하는 샘플 값입니다
    const TEST_IMP_KEY = 'imp_apikey';
    const TEST_IMP_SECRET = 'ekKoeW8RyKuT0zgaZsUtXXTLQ4AhPFW3ZGseDA6bkA5lamv9OqDMnxyeB9wqOsuO9W3Mx9YSJ4dTqJ3f';
    
    public function testErrorFromGetAuthToken()
    {
        $client = new HttpClient(new Configuration());
        try {
            $client->getAuthToken();
            static::fail();
        } catch (RuntimeException $e) {
            static::assertEquals(-1, $e->getCode());
            static::assertEquals('imp_key, imp_secret 파라메터가 누락되었습니다.', $e->getMessage());
        }
    }
    
    public function testGetAuthToken()
    {
        $client = new HttpClient(new Configuration([
            'imp_key' => static::TEST_IMP_KEY,
            'imp_secret' => static::TEST_IMP_SECRET,
        ]));
        
        // token
        static::assertRegExp('/^[a-f0-9]{40}$/', $client->getAuthToken());
    }
}
