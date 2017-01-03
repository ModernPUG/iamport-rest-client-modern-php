# 아임포트 PHP Rest Client

## Installation

**v0.3.x 이상**

```sh
composer require modern-pug/iamport-rest-client
```

**v0.2.x 이하**

```sh
composer require modern-pug/iamport-rest-client-modern-php
```

## 주의
이 프로젝트는 개발 중인 프로젝트입니다. 실제 서비스 사용시 유의하여 주시기 바랍니다.

## 사용법

```php
<?php

use ModernPUG\Iamport\IamportApi;
use \ModernPUG\Iamport\Configuration;

$client = new IamportApi(new Configuration([
    'imp_key' => 'your_key',
    'imp_secret' => 'your_secret',
]));

$client->getPaymentByImpId('imp_xxxxxxxxxx'); // return [ ... ] array
```

### 라라벨에서 사용하기

..작성중..
