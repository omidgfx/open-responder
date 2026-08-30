<?php

namespace Omidgfx\Responder\Facades;

use Illuminate\Support\Facades\Facade;
use Omidgfx\Responder\Contracts\Responder as ResponderContract;

/**
 * Facade for the responder service.
 *
 * @see \Omidgfx\Responder\Responder
 */
class Responder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ResponderContract::class;
    }
}
