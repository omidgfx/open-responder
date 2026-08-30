<?php

namespace Omidgfx\Responder\Contracts;

/**
 * A contract for resolving error messages from error codes.
 */
interface ErrorMessageResolver
{
    /**
     * Resolve a message from the given error code.
     *
     * @param  mixed $errorCode
     * @return string|null
     */
    public function resolve($errorCode);
}