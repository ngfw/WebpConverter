<?php

namespace Ngfw\WebpConverter;

use Illuminate\Support\Facades\Facade;

class WebpConverterFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'webpConverter';
    }
}