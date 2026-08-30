<?php

namespace Omidgfx\Responder\Exceptions;

use Omidgfx\Responder\Fractal\Serializer\SerializerAbstract;
use RuntimeException;

/**
 * Thrown when a value is supplied as a success serializer that is not a valid
 * Fractal serializer.
 */
class InvalidSuccessSerializerException extends RuntimeException
{
    /**
     * Construct the exception.
     */
    public function __construct()
    {
        parent::__construct('Serializer must be an instance of ['.SerializerAbstract::class.'].');
    }
}
