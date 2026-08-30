<?php

namespace Omidgfx\Responder\Http\Responses\Decorators;

use Illuminate\Http\JsonResponse;
use Omidgfx\Responder\Contracts\ResponseFactory;

/**
 * Base decorator wrapping a [ResponseFactory], letting subclasses tweak the
 * data, status and headers before the response is built.
 */
abstract class ResponseDecorator implements ResponseFactory
{
    /** @var ResponseFactory */
    protected $factory;

    /**
     * @param  \Omidgfx\Responder\Contracts\ResponseFactory  $factory
     */
    public function __construct(ResponseFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Generate a JSON response.
     *
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\JsonResponse
     */
    abstract public function make(array $data, int $status, array $headers = []): JsonResponse;
}
