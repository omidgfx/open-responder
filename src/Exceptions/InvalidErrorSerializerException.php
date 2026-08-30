<?php

namespace Omidgfx\Responder\Exceptions;

use Omidgfx\Responder\Contracts\ErrorSerializer;
use RuntimeException;

/**
 * Thrown when a value is supplied as an error serializer that is not a valid
 * [ErrorSerializer] implementation.
 */
class InvalidErrorSerializerException extends RuntimeException
{
    /**
     * Construct the exception.
     */
    public function __construct()
    {
        parent::__construct('Serializer must be an instance of ['.ErrorSerializer::class.'].');
    }
}
