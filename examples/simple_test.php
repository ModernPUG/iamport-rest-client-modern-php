<?php

require_once '../vendor/autoload.php';

// config
(new Dotenv\Dotenv(__DIR__))->load();
$key = getenv('IAMPORT_KEY');
$secret = getenv('IAMPORT_SECRET');

$iamport = new ModernPUG\Iamport\IamportApi($key, $secret);
$payments = $iamport->getPaymentStatus('all');
echo 'PAYMENTS_STATUS<br>';
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
$p1 = $iamport->findByImpUID($imp_uid);
assert($p1->imp_uid == $imp_uid);
assert($p1->merchant_uid == $merchant_uid);
dump($p1);
$p2 = $iamport->getPaymentsByUid($token, $imp_uid);
assert($p2->imp_uid == $imp_uid);
assert($p2->merchant_uid == $merchant_uid);
dump($p2);
if ($paidItem) {
    $cancelResult = $iamport->cancelPayment($token, $imp_uid, null, 1, 'no reason, just do it.', null, null, null);
    dump($cancelResult);
}
$uuid = uniqid('my_store_');
dump($uuid);
$prepare1 = $iamport->createPaymentPrepare($token, $uuid, 100);
dump($prepare1);
assert($prepare1->merchant_uid == $uuid);
assert($prepare1->amount == 100);
$prepare2 = $iamport->getPaymentPrepare($token, $uuid);
assert($prepare2->merchant_uid == $uuid);
assert($prepare2->amount == 100);
//+"imp_uid": "imp_555182949132"
//+"merchant_uid": "merchant_1455518207092"
// $client->getPaymentsByUid($at);

