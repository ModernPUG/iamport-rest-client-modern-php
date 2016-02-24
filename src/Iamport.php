<?php

namespace ModernPUG\Iamport;

use Exception;

class Iamport
{
    const GET_TOKEN_URL = 'https://api.iamport.kr/users/getToken';
    const GET_PAYMENT_URL = 'https://api.iamport.kr/payments/';
    const FIND_PAYMENT_URL = 'https://api.iamport.kr/payments/find/';
    const GET_PAYMENT_STATUS_URL = 'https://api.iamport.kr/payments/status/';
    const CANCEL_PAYMENT_URL = 'https://api.iamport.kr/payments/cancel/';
    const PREPARE_PAYMENT_URL = 'https://api.iamport.kr/payments/prepare/';
    const SBCR_ONETIME_PAYMENT_URL = 'https://api.iamport.kr/subscribe/payments/onetime/';
    const SBCR_AGAIN_PAYMENT_URL = 'https://api.iamport.kr/subscribe/payments/again/';
    const SBCR_SCHEDULE_PAYMENT_URL = 'https://api.iamport.kr/subscribe/payments/schedule/';
    const SBCR_UNSCHEDULE_PAYMENT_URL = 'https://api.iamport.kr/subscribe/payments/unschedule/';
    const SBCR_CUSTOMER_URL = 'https://api.iamport.kr/subscribe/customers/';
    const TOKEN_HEADER = 'Authorization';

    private $imp_key = null;
    private $imp_secret = null;
    private $access_token = null;
    private $expired_at = null;
    private $now = null;

    public function __construct($imp_key, $imp_secret)
    {
        $this->imp_key = $imp_key;
        $this->imp_secret = $imp_secret;
    }

    public function findByImpUID($imp_uid)
    {
        try {
            $response = $this->getResponse(self::GET_PAYMENT_URL . $imp_uid);

            $payment_data = new IamportPayment($response);
            return new IamportResult(true, $payment_data);
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function findByMerchantUID($merchant_uid)
    {
        try {
            $response = $this->getResponse(self::FIND_PAYMENT_URL . $merchant_uid);

            $payment_data = new IamportPayment($response);
            return new IamportResult(true, $payment_data);
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function getPaymentStatus($payment_status = 'all', $page = null)
    {
        try {
            $request_url = self::GET_PAYMENT_STATUS_URL . $payment_status;
            if ($page) {
                $request_url .= '?page=' . $page;
            }
            $response = $this->getResponse($request_url);

            return $response->list;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function cancel($data)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('amount', 'reason', 'refund_holder', 'refund_bank', 'refund_account'));
            $cancel_data = array_intersect_key($data, $keys);
            if ($data['imp_uid']) {
                $cancel_data['imp_uid'] = $data['imp_uid'];
            } elseif ($data['merchant_uid']) {
                $cancel_data['merchant_uid'] = $data['merchant_uid'];
            } else {
                return new IamportResult(false, null, array('code' => '', 'message' => '취소하실 imp_uid 또는 merchant_uid 중 하나를 지정하셔야 합니다.'));
            }
            $response = $this->postResponse(
                self::CANCEL_PAYMENT_URL,
                $cancel_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            $payment_data = new IamportPayment($response);
            return new IamportResult(true, $payment_data);
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function preparePayment($data)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('token', 'merchant_uid', 'amount'));
            $onetime_data = array_intersect_key($data, $keys);
            $response = $this->postResponse(
                self::PREPARE_PAYMENT_URL,
                $onetime_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            return $response;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function getPreparePayment($merchant_uid)
    {
        try {
            $request_url = self::PREPARE_PAYMENT_URL . $merchant_uid;
            $response = $this->getResponse($request_url);
            return $response;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function sbcr_onetime($data)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('token', 'merchant_uid', 'amount', 'vat', 'card_number', 'expiry', 'birth', 'pwd_2digit', 'remember_me', 'customer_uid', 'name', 'buyer_name', 'buyer_email', 'buyer_tel', 'buyer_addr', 'buyer_postcode'));
            $onetime_data = array_intersect_key($data, $keys);
            $response = $this->postResponse(
                self::SBCR_ONETIME_PAYMENT_URL,
                $onetime_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            $payment_data = new IamportPayment($response);
            return new IamportResult(true, $payment_data);
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function sbcr_again($data)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('token', 'customer_uid', 'merchant_uid', 'amount', 'vat', 'name', 'buyer_name', 'buyer_email', 'buyer_tel', 'buyer_addr', 'buyer_postcode'));
            $onetime_data = array_intersect_key($data, $keys);
            $response = $this->postResponse(
                self::SBCR_AGAIN_PAYMENT_URL,
                $onetime_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            $payment_data = new IamportPayment($response);
            return new IamportResult(true, $payment_data);
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    //TODO: schedules param 보내기 테스트 필요
    public function sbcr_schedule($data)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('token', 'customer_uid', 'checking_amount', 'card_number', 'expiry', 'birth', 'pwd_2digit', 'schedules'));
            $onetime_data = array_intersect_key($data, $keys);
            $response = $this->postResponse(
                self::SBCR_SCHEDULE_PAYMENT_URL,
                $onetime_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            return $response;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function sbcr_unschedule($data)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('token', 'customer_uid', 'merchant_uid'));
            $onetime_data = array_intersect_key($data, $keys);
            $response = $this->postResponse(
                self::SBCR_UNSCHEDULE_PAYMENT_URL,
                $onetime_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            return $response;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function delete_subscribe_customers($customer_uid)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('token'));
            $onetime_data = array_intersect_key(array(), $keys);
            $response = $this->deleteResponse(
                self::SBCR_CUSTOMER_URL . $customer_uid,
                $onetime_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            return $response;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function get_subscribe_customers($customer_uid)
    {
        try {
            $request_url = self::SBCR_CUSTOMER_URL . $customer_uid;
            $response = $this->getResponse($request_url);
            return $response;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    public function post_subscribe_customers($customer_uid, $data)
    {
        try {
            $access_token = $this->getAccessCode();
            $keys = array_flip(array('token', 'card_number', 'expiry', 'birth', 'pwd_2digit'));
            $onetime_data = array_intersect_key($data, $keys);
            $response = $this->postResponse(
                self::SBCR_CUSTOMER_URL . $customer_uid,
                $onetime_data,
                array(self::TOKEN_HEADER . ': ' . $access_token)
            );
            return $response;
        } catch (IamportAuthException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (IamportRequestException $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        } catch (Exception $e) {
            return new IamportResult(false, null, array('code' => $e->getCode(), 'message' => $e->getMessage()));
        }
    }

    private function getResponse($request_url, $request_data = null)
    {
        $access_token = $this->getAccessCode();
        $headers = array(self::TOKEN_HEADER . ': ' . $access_token, 'Content-Type: application/json');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //execute get
        $body = curl_exec($ch);
        $error_code = curl_errno($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $r = json_decode(trim($body));
        curl_close($ch);
        if ($error_code > 0) throw new Exception("Request Error(HTTP STATUS : " . $status_code . ")", $error_code);
        if (empty($r)) throw new Exception("API서버로부터 응답이 올바르지 않습니다. " . $body, 1);
        if ($r->code !== 0) throw new IamportRequestException($r);
        return $r->response;
    }

    private function postResponse($request_url, $post_data = array(), $headers = array())
    {
        $post_data_str = json_encode($post_data);
        $default_header = array('Content-Type: application/json', 'Content-Length: ' . strlen($post_data_str));
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
        if ($error_code > 0) throw new Exception("AccessCode Error(HTTP STATUS : " . $status_code . ")", $error_code);
        if (empty($r)) throw new Exception("API서버로부터 응답이 올바르지 않습니다. " . $body, 1);
        if ($r->code !== 0) throw new IamportRequestException($r);
        return $r->response;
    }

    private function deleteResponse($request_url, $delete_data = array(), $headers = array())
    {
        $post_data_str = json_encode($delete_data);
        $default_header = array('Content-Type: application/json', 'Content-Length: ' . strlen($post_data_str));
        $headers = array_merge($default_header, $headers);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_str);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $body = curl_exec($ch);
        $error_code = curl_errno($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $r = json_decode(trim($body));
        curl_close($ch);
        if ($error_code > 0) throw new Exception("AccessCode Error(HTTP STATUS : " . $status_code . ")", $error_code);
        if (empty($r)) throw new Exception("API서버로부터 응답이 올바르지 않습니다. " . $body, 1);
        if ($r->code !== 0) throw new IamportRequestException($r);
        return $r->response;
    }

    private function getAccessCode()
    {
        try {
            $now = time();
            if ($now < $this->expired_at && !empty($this->access_token)) return $this->access_token;
            $this->expired_at = null;
            $this->access_token = null;
            $response = $this->postResponse(
                self::GET_TOKEN_URL,
                array(
                    'imp_key' => $this->imp_key,
                    'imp_secret' => $this->imp_secret
                )
            );
            $offset = $response->expired_at - $response->now;
            $this->expired_at = time() + $offset;
            $this->access_token = $response->access_token;
            return $response->access_token;
        } catch (Exception $e) {
            throw new IamportAuthException('[API인증오류] ' . $e->getMessage(), $e->getCode());
        }
    }
}
