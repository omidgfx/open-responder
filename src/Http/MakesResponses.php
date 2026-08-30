<?php

namespace Omidgfx\Responder\Http;

use Omidgfx\Responder\Http\Responses\ErrorResponseBuilder;
use Omidgfx\Responder\Http\Responses\SuccessResponseBuilder;
use Omidgfx\Responder\Responder;

/**
 * Controller helper trait exposing `success()` and `error()` shortcut methods.
 */
trait MakesResponses
{
    /**
     * Build a successful response.
     *
     * @param  mixed  $data
     * @param  callable|string|\Omidgfx\Responder\Transformers\Transformer|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder
     */
    public function success($data = null, $transformer = null, ?string $resourceKey = null): SuccessResponseBuilder
    {
        return app(Responder::class)->success(...func_get_args());
    }

    /**
     * Build an error response.
     *
     * @param  mixed|null  $errorCode
     * @param  string|null  $message
     * @return \Omidgfx\Responder\Http\Responses\ErrorResponseBuilder
     */
    public function error($errorCode = null, ?string $message = null): ErrorResponseBuilder
    {
        return app(Responder::class)->error(...func_get_args());
    }
}
