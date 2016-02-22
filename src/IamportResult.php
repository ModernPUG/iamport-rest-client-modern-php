<?php

namespace ModernPUG\Iamport;

class IamportResult
{
    public $success = false;
    public $data;
    public $error;

    public function __construct($success = false, $data = null, $error = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
    }
}
