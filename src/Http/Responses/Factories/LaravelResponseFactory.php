<?php

namespace Omidgfx\Responder\Http\Responses\Factories;

use Illuminate\Contracts\Routing\ResponseFactory as BaseLaravelResponseFactory;
use Illuminate\Http\JsonResponse;
use Omidgfx\Responder\Contracts\ResponseFactory;

/**
 * Makes JSON responses through Laravel's routing response factory.
 */
class LaravelResponseFactory implements ResponseFactory
{
    /** @var \Illuminate\Contracts\Routing\ResponseFactory */
    protected $factory;

    /**
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $factory
     */
    public function __construct(BaseLaravelResponseFactory $factory)
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
    public function make(array $data, int $status, array $headers = []): JsonResponse
    {
        return $this->factory->json($data, $status, $headers);
    }
}
