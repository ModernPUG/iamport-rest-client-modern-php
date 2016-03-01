<?php
use ModernPUG\Iamport\IamportApi;

require_once '../vendor/autoload.php';

// config
(new Dotenv\Dotenv(__DIR__))->load();
$key = getenv('IAMPORT_KEY');
$secret = getenv('IAMPORT_SECRET');

try {
    $api = new IamportApi($key, $secret);
// payment Page
    $paymentPage = $api->getPaymentPage('all');

    echo 'PAYMENTS_STATUS<br>';
    dump($paymentPage);
    $payments = $paymentPage->list;
    dump($payments);

    $selectedItem = $payments[0];
    $paidItem = '';
    foreach ($payments as $item) {
        if ($item->status == 'paid') {
            echo 'PAID ITEM<br>';
            $paidItem = $item;
            break;
        }
    }
    if ($paidItem) {
        $selectedItem = $paidItem;
    }

    $imp_uid = $selectedItem->imp_uid;
    $merchant_uid = $selectedItem->merchant_uid;

    exit;
// 일단 샘플 형태만 . 아래는 동작하지 않음. 동작하도록 변경 필요.
//dump($imp_uid);
    $p1 = $api->findByImpUID($imp_uid);
    assert($p1->imp_uid == $imp_uid);
    assert($p1->merchant_uid == $merchant_uid);
    dump($p1);
    $p2 = $api->getPaymentsByUid($token, $imp_uid);
    assert($p2->imp_uid == $imp_uid);
    assert($p2->merchant_uid == $merchant_uid);
    dump($p2);
    if ($paidItem) {
        $cancelResult = $api->cancelPayment($token, $imp_uid, null, 1, 'no reason, just do it.', null, null, null);
        dump($cancelResult);
    }
    $uuid = uniqid('my_store_');
    dump($uuid);
    $prepare1 = $api->createPaymentPrepare($token, $uuid, 100);
    dump($prepare1);
    assert($prepare1->merchant_uid == $uuid);
    assert($prepare1->amount == 100);
    $prepare2 = $api->getPaymentPrepare($token, $uuid);
    assert($prepare2->merchant_uid == $uuid);
    assert($prepare2->amount == 100);
//+"imp_uid": "imp_555182949132"
//+"merchant_uid": "merchant_1455518207092"
// $client->getPaymentsByUid($at);


} catch (\ModernPUG\Iamport\Exception\AuthException $e) {
    // 인증 관련 예외
    die('[IAMPORT] auth failed : ' . $e->getMessage());
} catch (\ModernPUG\Iamport\Exception\RuntimeException $e) {
    // 기타 패키지에서 핸들링 된 예외
    die('[IAMPORT] exception raised : ' . $e->getMessage());
} catch (\Exception $e) {
    die('Ooooooops! UnExpected Exception please investigate the code : ' . $e->getMessage());
}
