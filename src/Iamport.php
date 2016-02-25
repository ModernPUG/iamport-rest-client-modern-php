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

        return $result->response;
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
        $response = $this->httpGet("https://api.iamport.kr/payments/$id");
        $payment_data = new IamportPayment($response);

        return new IamportResult(true, $payment_data);
    }

    public function getPaymentByMerchantId($id)
    {
        $response = $this->httpGet("https://api.iamport.kr/payments/find/$id");
        $payment_data = new IamportPayment($response);

        return new IamportResult(true, $payment_data);
    }

    public function getPaymentList($status = 'all', $page = null)
    {
        $response = $this->httpGet("https://api.iamport.kr/payments/status/$status".($page ? "?page=$page" : ''));

        return $response->list;
    }

    public function cancel($data)
    {
        $keys = array_flip(['amount', 'reason', 'refund_holder', 'refund_bank', 'refund_account']);
        $cancel_data = array_intersect_key($data, $keys);
        if ($data['imp_uid']) {
            $cancel_data['imp_uid'] = $data['imp_uid'];
        } elseif ($data['merchant_uid']) {
            $cancel_data['merchant_uid'] = $data['merchant_uid'];
        } else {
            return new IamportResult(false, null, [
                'code' => '',
                'message' => '취소하실 imp_uid 또는 merchant_uid 중 하나를 지정하셔야 합니다.',
            ]);
        }
        $response = $this->httpPost('https://api.iamport.kr/payments/cancel/', $cancel_data);
        $payment_data = new IamportPayment($response);

        return new IamportResult(true, $payment_data);
    }

    public function preparePayment($data)
    {
        $keys = array_flip(['token', 'merchant_uid', 'amount']);
        $data = array_intersect_key($data, $keys);
        $response = $this->httpPost('https://api.iamport.kr/payments/prepare/', $data);

        return $response;
    }

    public function getPreparePayment($merchant_uid)
    {
        $response = $this->httpGet("https://api.iamport.kr/payments/prepare/$merchant_uid");

        return $response;
    }

    public function sbcr_onetime($data)
    {
        $keys = array_flip([
            'token',
            'merchant_uid', 'amount', 'vat', 'card_number', 'expiry', 'birth', 'pwd_2digit', 'remember_me',
            'customer_uid', 'name',
            'buyer_name', 'buyer_email', 'buyer_tel', 'buyer_addr', 'buyer_postcode',
        ]);
        $data = array_intersect_key($data, $keys);
        $response = $this->httpPost('https://api.iamport.kr/subscribe/payments/onetime/', $data);
        $payment_data = new IamportPayment($response);

        return new IamportResult(true, $payment_data);
    }

    public function sbcr_again($data)
    {
        $keys = array_flip([
            'token',
            'customer_uid', 'merchant_uid', 'amount', 'vat', 'name',
            'buyer_name', 'buyer_email', 'buyer_tel', 'buyer_addr', 'buyer_postcode',
        ]);
        $data = array_intersect_key($data, $keys);
        $response = $this->httpPost('https://api.iamport.kr/subscribe/payments/again/', $data);
        $payment_data = new IamportPayment($response);

        return new IamportResult(true, $payment_data);
    }

    //TODO: schedules param 보내기 테스트 필요
    public function sbcr_schedule($data)
    {
        $keys = array_flip([
            'token', 'customer_uid', 'checking_amount', 'card_number', 'expiry', 'birth', 'pwd_2digit', 'schedules',
        ]);
        $data = array_intersect_key($data, $keys);
        $response = $this->httpPost('https://api.iamport.kr/subscribe/payments/schedule/', $data);

        return $response;
    }

    public function sbcr_unschedule($data)
    {
        $keys = array_flip(['token', 'customer_uid', 'merchant_uid']);
        $data = array_intersect_key($data, $keys);
        $response = $this->httpPost('https://api.iamport.kr/subscribe/payments/unschedule/', $data);

        return $response;
    }

    public function delete_subscribe_customers($customer_uid)
    {
        $response = $this->httpDelete("https://api.iamport.kr/subscribe/customers/$customer_uid", null);

        return $response;
    }

    public function get_subscribe_customers($customer_uid)
    {
        $response = $this->httpGet("https://api.iamport.kr/subscribe/customers/$customer_uid");

        return $response;
    }

    public function post_subscribe_customers($customer_uid, $data)
    {
        $keys = array_flip(['token', 'card_number', 'expiry', 'birth', 'pwd_2digit']);
        $data = array_intersect_key($data, $keys);
        $response = $this->httpPost("https://api.iamport.kr/subscribe/customers/$customer_uid", $data);

        return $response;
    }
}
