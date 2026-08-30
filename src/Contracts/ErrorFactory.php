<?php

namespace Omidgfx\Responder\Contracts;

/**
 * A contract for creating errors.
 */
interface ErrorFactory
{
    /**
     * Make an error array from the given error code, message and error data.
     *
     * @param  \Omidgfx\Responder\Contracts\ErrorSerializer  $serializer
     * @param  mixed|null  $errorCode
     * @param  string|null  $message
     * @param  array|null  $data
     * @return array
     */
    public function make(ErrorSerializer $serializer, $errorCode = null, ?string $message = null, ?array $data = null): array;
}
