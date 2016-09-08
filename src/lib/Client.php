<?php

namespace n1k3\WordpressApi\Lib;

class Client
{
    /** @var string  */
    public $id;
    /** @var string  */
    public $secret;
    /** @var string|null  */
    public $callbackUrl;

    /**
     * @param string $id
     * @param string $secret
     * @param null $callbackUrl
     */
    public function __construct($id, $secret, $callbackUrl = null)
    {
        $this->id = $id;
        $this->secret = $secret;
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Client[id=$this->id,secret=$this->secret]";
    }
}
