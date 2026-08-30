<?php

namespace Omidgfx\Responder\Facades;

use Illuminate\Support\Facades\Facade;
use Omidgfx\Responder\Transformation as TransformationService;

/**
 * Facade for the (non-serializing) transformation service.
 *
 * @see \Omidgfx\Responder\Transformation
 */
class Transformation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return TransformationService::class;
    }
}
