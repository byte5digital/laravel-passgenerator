<?php

namespace Byte5Digital\Facades;

use Illuminate\Support\Facades\Facade;

class PassGenerator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'passgenerator';
    }
}
