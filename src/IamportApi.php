<?php

namespace ModernPUG\Iamport;

class IamportApi
{
    private $client;

    public function __construct($imp_key, $imp_secret, IamportHttpClient $client = null)
    {
        $this->imp_key = $imp_key;
        $this->imp_secret = $imp_secret;
        $this->client = $client ?: new IamportHttpClient($imp_key, $imp_secret);
    }

    public function getPaymentByImpId($id)
    {
        return $this->client->httpGet(
            "https://api.iamport.kr/payments/$id"
        );
    }

    public function getPaymentByMerchantId($id)
    {
        return $this->client->httpGet(
            "https://api.iamport.kr/payments/find/$id"
        );
    }

    public function getPaymentList($status = 'all', $page = null)
    {
        return $this->client->httpGet(
            "https://api.iamport.kr/payments/status/$status".($page ? "?page=$page" : '')
        );
    }

    public function cancel($data)
    {
        //TODO: imp_uid, merchant_uid 둘 다 없는 경우 서버에서 무엇을 주길래 이런 처리가 되어 있을까?
        if (!isset($data['imp_uid']) && !isset($data['merchant_uid'])) {
            return new IamportResult(false, null, [
                'code' => '',
                'message' => '취소하실 imp_uid 또는 merchant_uid 중 하나를 지정하셔야 합니다.',
            ]);
        }

        return $this->client->httpPost(
            'https://api.iamport.kr/payments/cancel/',
            $this->only($data, [
                'amount', 'reason', 'refund_holder', 'refund_bank', 'refund_account',
            ])
        );
    }

    public function preparePayment($data)
    {
        return $this->client->httpPost(
            'https://api.iamport.kr/payments/prepare/',
            $this->only($data, [
                'token', 'merchant_uid', 'amount',
            ])
        );
    }

    public function getPreparePayment($merchant_uid)
    {
        return $this->client->httpGet(
            "https://api.iamport.kr/payments/prepare/$merchant_uid"
        );
    }

    public function sbcr_onetime($data)
    {
        return $this->client->httpPost(
            'https://api.iamport.kr/subscribe/payments/onetime/',
            $this->only($data, [
                'token',
                'merchant_uid', 'amount', 'vat', 'card_number', 'expiry', 'birth', 'pwd_2digit', 'remember_me',
                'customer_uid', 'name',
                'buyer_name', 'buyer_email', 'buyer_tel', 'buyer_addr', 'buyer_postcode',
            ])
        );
    }

    public function sbcr_again($data)
    {
        return $this->client->httpPost(
            'https://api.iamport.kr/subscribe/payments/again/',
            $this->only($data, [
                'token',
                'customer_uid', 'merchant_uid', 'amount', 'vat', 'name',
                'buyer_name', 'buyer_email', 'buyer_tel', 'buyer_addr', 'buyer_postcode',
            ])
        );
    }

    public function sbcr_schedule($data)
    {
        //TODO: schedules param 보내기 테스트 필요
        return $this->client->httpPost(
            'https://api.iamport.kr/subscribe/payments/schedule/',
            $this->only($data, [
                'token',
                'customer_uid', 'checking_amount', 'card_number', 'expiry', 'birth', 'pwd_2digit', 'schedules',
            ])
        );
    }

    public function sbcr_unschedule($data)
    {
        return $this->client->httpPost(
            'https://api.iamport.kr/subscribe/payments/unschedule/',
            $this->only($data, [
                'token',
                'customer_uid', 'merchant_uid',
            ])
        );
    }

    public function delete_subscribe_customers($customer_uid)
    {
        return $this->client->httpDelete(
            "https://api.iamport.kr/subscribe/customers/$customer_uid"
        );
    }

    public function get_subscribe_customers($customer_uid)
    {
        return $this->client->httpGet(
            "https://api.iamport.kr/subscribe/customers/$customer_uid"
        );
    }

    public function post_subscribe_customers($customer_uid, $data)
    {
        return $this->client->httpPost(
            "https://api.iamport.kr/subscribe/customers/$customer_uid",
            $this->only($data, [
                'token',
                'card_number', 'expiry', 'birth', 'pwd_2digit',
            ])
        );
    }

    private function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}
