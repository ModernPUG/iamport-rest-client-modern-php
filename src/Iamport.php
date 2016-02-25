<?php

namespace ModernPUG\Iamport;

use Exception;
use GuzzleHttp\Client as Guzzle;

class Iamport
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

    private function postResponse($request_url, $post_data = array(), $headers = array())
    {
        $post_data_str = json_encode($post_data);
        $default_header = array('Content-Type: application/json', 'Content-Length: '.strlen($post_data_str));
        $headers = array_merge($default_header, $headers);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_str);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $body = curl_exec($ch);
        $error_code = curl_errno($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $r = json_decode(trim($body));
        curl_close($ch);
        if ($error_code > 0) {
            throw new Exception('AccessCode Error(HTTP STATUS : '.$status_code.')', $error_code);
        }
        if (empty($r)) {
            throw new Exception('API 서버로부터 응답이 올바르지 않습니다. '.$body, 1);
        }
        if ($r->code !== 0) {
            throw new IamportRequestException($r);
        }

        return $r->response;
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
            $response = $this->postResponse(
                'https://api.iamport.kr/users/getToken',
                array(
                    'imp_key' => $this->imp_key,
                    'imp_secret' => $this->imp_secret,
                )
            );
            $offset = $response->expired_at - $response->now;
            $this->expired_at = time() + $offset;
            $this->access_token = $response->access_token;

            return $response->access_token;
        } catch (Exception $e) {
            throw new IamportAuthException('[API 인증오류] '.$e->getMessage(), $e->getCode());
        }
    }

    private function httpCall($type, $url, $body = null)
    {
        $access_token = $this->getAccessCode();
        $options = ['headers' => ['Authorization' => $access_token, 'Content-Type' => 'application/json']];
        if ($body) {
            $options = array_merge($options, ['body' => $body]);
        }
        $res = $this->client->request($type, $url, $options);
        $contents = $res->getBody()->getContents();
        $result = json_decode(trim($contents));

        return $result;
    }

    private function httpGet($url)
    {
        return $this->httpCall('GET', $url);
    }

    private function httpPost($url, $data = null)
    {
        $data = $data ? json_encode($data) : null;

        return $this->httpCall('POST', $url, $data);
    }

    private function httpDelete($url, $data = null)
    {
        $data = $data ? json_encode($data) : null;

        return $this->httpCall('DELETE', $url, $data);
    }

    public function getPaymentByImpId($id)
    {
        return $this->httpGet(
            "https://api.iamport.kr/payments/$id"
        );
    }

    public function getPaymentByMerchantId($id)
    {
        return $this->httpGet(
            "https://api.iamport.kr/payments/find/$id"
        );
    }

    public function getPaymentList($status = 'all', $page = null)
    {
        $response = $this->httpGet(
            "https://api.iamport.kr/payments/status/$status".($page ? "?page=$page" : '')
        );

        return $response;
    }

    public function cancel($data)
    {
        if (!isset($data['imp_uid']) && !isset($data['merchant_uid'])) {
            return new IamportResult(false, null, [
                'code' => '',
                'message' => '취소하실 imp_uid 또는 merchant_uid 중 하나를 지정하셔야 합니다.',
            ]);
        }

        return $this->httpPost(
            'https://api.iamport.kr/payments/cancel/',
            $this->only($data, [
                'amount', 'reason', 'refund_holder', 'refund_bank', 'refund_account',
            ])
        );
    }

    public function preparePayment($data)
    {
        return $this->httpPost(
            'https://api.iamport.kr/payments/prepare/',
            $this->only($data, [
                'token', 'merchant_uid', 'amount',
            ])
        );
    }

    public function getPreparePayment($merchant_uid)
    {
        return $this->httpGet(
            "https://api.iamport.kr/payments/prepare/$merchant_uid"
        );
    }

    public function sbcr_onetime($data)
    {
        return $this->httpPost(
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
        return $this->httpPost(
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
        return $this->httpPost(
            'https://api.iamport.kr/subscribe/payments/schedule/',
            $this->only($data, [
                'token',
                'customer_uid', 'checking_amount', 'card_number', 'expiry', 'birth', 'pwd_2digit', 'schedules',
            ])
        );
    }

    public function sbcr_unschedule($data)
    {
        return $this->httpPost(
            'https://api.iamport.kr/subscribe/payments/unschedule/',
            $this->only($data, [
                'token', 'customer_uid', 'merchant_uid',
            ])
        );
    }

    public function delete_subscribe_customers($customer_uid)
    {
        return $this->httpDelete(
            "https://api.iamport.kr/subscribe/customers/$customer_uid"
        );
    }

    public function get_subscribe_customers($customer_uid)
    {
        return $this->httpGet(
            "https://api.iamport.kr/subscribe/customers/$customer_uid"
        );
    }

    public function post_subscribe_customers($customer_uid, $data)
    {
        return $this->httpPost(
            "https://api.iamport.kr/subscribe/customers/$customer_uid",
            $this->only($data, [
                'token', 'card_number', 'expiry', 'birth', 'pwd_2digit',
            ])
        );
    }

    private function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}
