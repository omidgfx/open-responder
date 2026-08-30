<?php

namespace Omidgfx\Responder\Contracts;

use Omidgfx\Responder\Http\Responses\ErrorResponseBuilder;
use Omidgfx\Responder\Http\Responses\SuccessResponseBuilder;

/**
 * A contract for responding with error- and success responses.
 */
interface Responder
{
    /**
     * Build a successful response.
     *
     * @param  mixed  $data
     * @param  callable|string|\Omidgfx\Responder\Transformers\Transformer|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder
     */
    public function success($data = null, $transformer = null, ?string $resourceKey = null): SuccessResponseBuilder;

    /**
     * Build an error response.
     *
     * @param  mixed|null  $errorCode
     * @param  string|null  $message
     * @return \Omidgfx\Responder\Http\Responses\ErrorResponseBuilder
     */
    public function error($errorCode = null, ?string $message = null): ErrorResponseBuilder;
}
