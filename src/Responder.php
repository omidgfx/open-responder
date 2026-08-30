<?php

namespace Omidgfx\Responder;

use Omidgfx\Responder\Contracts\Responder as ResponderContract;
use Omidgfx\Responder\Http\Responses\ErrorResponseBuilder;
use Omidgfx\Responder\Http\Responses\SuccessResponseBuilder;

/**
 * The responder is the entry point for building either a success or an error
 * response. It simply delegates to the appropriate dedicated builder.
 */
class Responder implements ResponderContract
{
    /** @var SuccessResponseBuilder */
    protected $successResponseBuilder;

    /** @var ErrorResponseBuilder */
    protected $errorResponseBuilder;

    /**
     * @param  SuccessResponseBuilder  $successResponseBuilder
     * @param  ErrorResponseBuilder  $errorResponseBuilder
     */
    public function __construct(SuccessResponseBuilder $successResponseBuilder, ErrorResponseBuilder $errorResponseBuilder)
    {
        $this->successResponseBuilder = $successResponseBuilder;
        $this->errorResponseBuilder = $errorResponseBuilder;
    }

    /**
     * Build a successful response using the given data, optional transformer and
     * resource key.
     *
     * @param  mixed  $data
     * @param  callable|string|\Omidgfx\Responder\Transformers\Transformer|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder
     */
    public function success($data = null, $transformer = null, ?string $resourceKey = null): SuccessResponseBuilder
    {
        return $this->successResponseBuilder->transform($data, $transformer, $resourceKey);
    }

    /**
     * Build an error response using the given error code and optional message.
     *
     * @param  mixed|null  $errorCode
     * @param  string|null  $message
     * @return \Omidgfx\Responder\Http\Responses\ErrorResponseBuilder
     */
    public function error($errorCode = null, ?string $message = null): ErrorResponseBuilder
    {
        return $this->errorResponseBuilder->error($errorCode, $message);
    }
}
