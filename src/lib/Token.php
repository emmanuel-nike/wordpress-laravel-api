<?php

namespace n1k3\WordpressApi\Lib;

use n1k3\WordpressApi\Lib\Util;

class Token
{
    /** @var string */
    public $token;

    /**
     * @param string $token The OAuth Access Token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf("token=%s",
            Util::urlencodeRfc3986($this->token)
        );
    }
}
