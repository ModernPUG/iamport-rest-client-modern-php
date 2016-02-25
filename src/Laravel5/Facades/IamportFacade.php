<?php

namespace ModernPUG\Iamport\Laravel5\Facades;

use Illuminate\Support\Facades\Facade;
use ModernPUG\Iamport\Iamport;

class IamportFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Iamport::class;
    }
}
