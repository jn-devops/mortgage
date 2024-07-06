<?php

namespace Homeful\Mortgage\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Homeful\Mortgage\Mortgage
 */
class Mortgage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Homeful\Mortgage\Mortgage::class;
    }
}
