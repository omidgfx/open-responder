<?php

namespace Omidgfx\Responder\Exceptions;

use Omidgfx\Responder\Transformers\Transformer;
use RuntimeException;

/**
 * Thrown when a transformer value is neither callable nor a [Transformer].
 */
class InvalidTransformerException extends RuntimeException
{
    /**
     * Construct the exception.
     */
    public function __construct()
    {
        parent::__construct('Transformer must be a callable or an instance of ['.Transformer::class.'].');
    }
}
