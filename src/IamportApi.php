<?php

namespace ModernPUG\Iamport;

class IamportApi
{
    const PAYMENT_STATUS_ALL = 'all';
    const PAYMENT_STATUS_READY = 'ready';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_CANCELED = 'cancelled';
    const PAYMENT_STATUS_FAILED = 'failed';

    /** @var \ModernPUG\Iamport\HttpClient */
    private $client;

    /**
     * @param \ModernPUG\Iamport\HttpClient|\ModernPUG\Iamport\Configuration $clientOrConfig
     */
    public function __construct($clientOrConfig)
    {
        if ($clientOrConfig instanceof HttpClient) {
            $this->client = $clientOrConfig;
        } elseif ($clientOrConfig instanceof Configuration) {
            $this->client = new HttpClient($clientOrConfig);
        }
    }

    /**
     * @param string $id
     * @return array
     */
    public function getPaymentByImpId($id)
    {
        return $this->client->httpGet("/payments/{$id}");
    }

    /**
     * @param string $id
     * @return array
     */
    public function getPaymentByMerchantId($id)
    {
        return $this->client->httpGet("/payments/find/{$id}");
    }

    /**
     * @param string $status
     * @param integer $page
     * @return array
     */
    public function getPaymentPage($status = 'all', $page = null)
    {
        return $this->client->httpGet(
            "/payments/status/{$status}" . ($page ? "?page={$page}" : '')
        );
    }
    
    public function cancel(array $data)
    {
        //TODO: imp_uid, merchant_uid 둘 다 없는 경우 서버에서 무엇을 주길래 이런 처리가 되어 있을까?
        if (!isset($data['imp_uid']) && !isset($data['merchant_uid'])) {
            return [
                'code' => '',
                'message' => '취소하실 imp_uid 또는 merchant_uid 중 하나를 지정하셔야 합니다.',
            ];
        }

        //TODO: 얘는 post 인데 token 이란 것이 없다. token 은 무엇인가?
        return $this->client->httpPost(
            '/payments/cancel/',
            $this->only($data, [
                'amount',
                'reason',
                'refund_holder',
                'refund_bank',
                'refund_account',
            ])
        );
    }

    public function preparePayment(array $data)
    {
        return $this->client->httpPost(
            '/payments/prepare/',
            $this->only($data, [
                'token',
                'merchant_uid',
                'amount',
            ])
        );
    }

    public function getPreparePayment($merchant_uid)
    {
        return $this->client->httpGet(
            "/payments/prepare/$merchant_uid"
        );
    }

    public function subscribeOnetime(array $data)
    {
        return $this->client->httpPost(
            '/subscribe/payments/onetime/',
            $this->only($data, [
                'token',
                'merchant_uid',
                'amount',
                'vat',
                'card_number',
                'expiry',
                'birth',
                'pwd_2digit',
                'remember_me',
                'customer_uid',
                'name',
                'buyer_name',
                'buyer_email',
                'buyer_tel',
                'buyer_addr',
                'buyer_postcode',
            ])
        );
    }

    public function subscribeAgain($data)
    {
        return $this->client->httpPost(
            '/subscribe/payments/again/',
            $this->only($data, [
                'token',
                'customer_uid',
                'merchant_uid',
                'amount',
                'vat',
                'name',
                'buyer_name',
                'buyer_email',
                'buyer_tel',
                'buyer_addr',
                'buyer_postcode',
            ])
        );
    }

    public function subscribeSchedule(array $data)
    {
        //TODO: schedules param 보내기 테스트 필요
        return $this->client->httpPost(
            '/subscribe/payments/schedule/',
            $this->only($data, [
                'token',
                'customer_uid',
                'checking_amount',
                'card_number',
                'expiry',
                'birth',
                'pwd_2digit',
                'schedules',
            ])
        );
    }

    public function subscribeUnschedule(array $data)
    {
        return $this->client->httpPost(
            '/subscribe/payments/unschedule/',
            $this->only($data, [
                'token',
                'customer_uid',
                'merchant_uid',
            ])
        );
    }

    public function deleteSubscribeCustomers($customer_uid)
    {
        return $this->client->httpDelete(
            "/subscribe/customers/$customer_uid"
        );
    }

    public function getSubscribeCustomers($customer_uid)
    {
        return $this->client->httpGet(
            "/subscribe/customers/$customer_uid"
        );
    }

    public function postSubscribeCustomers($customer_uid, array $data)
    {
        return $this->client->httpPost(
            "/subscribe/customers/$customer_uid",
            $this->only($data, [
                'token',
                'card_number',
                'expiry',
                'birth',
                'pwd_2digit',
            ])
        );
    }

    private function only(array $array, $keys)
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }
}
