<?php

namespace ModernPUG\Iamport;

class Configuration
{
    /** @var string */
    private $imp_key;
    
    /** @var string */
    private $imp_secret;
    
    /** @var string */
    private $host = 'https://api.iamport.kr';

    public function __construct(array $configs = [])
    {
        foreach ($configs as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return string
     */
    public function getImpKey()
    {
        return $this->imp_key;
    }

    /**
     * @return string
     */
    public function getImpSecret()
    {
        return $this->imp_secret;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }
}
