<?php

namespace Byte5\Facades;

use Illuminate\Support\Facades\Facade;

class PassGenerator extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'passgenerator';
    }
}
