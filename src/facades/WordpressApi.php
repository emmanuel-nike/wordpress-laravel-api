<?php 

namespace n1k3\WordpressApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mbarwick83\Twitter\Twitter
 */
class WordpressApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'n1k3\WordpressApi\WordpressApi';
    }
}