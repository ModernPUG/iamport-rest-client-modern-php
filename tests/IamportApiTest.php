<?php
namespace ModernPUG\Iamport;

use PHPUnit_Framework_TestCase;

class IamportApiTest extends PHPUnit_Framework_TestCase
{
    public function testGetPayments()
    {
        $client = new IamportApi(new Configuration([
            'imp_key' => HttpClientTest::TEST_IMP_KEY,
            'imp_secret' => HttpClientTest::TEST_IMP_SECRET,
        ]));
       
        $result = $client->getPaymentPage(IamportApi::PAYMENT_STATUS_PAID);
        static::assertArrayHasKey('total', $result);
        static::assertArrayHasKey('previous', $result);
        static::assertArrayHasKey('next', $result);
        static::assertArrayHasKey('list', $result);

        static::assertArrayHasKey('amount', $result['list'][0]);
    }
}
