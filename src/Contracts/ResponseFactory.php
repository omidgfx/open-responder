<?php

namespace Omidgfx\Responder\Contracts;

use Illuminate\Http\JsonResponse;

/**
 * A contract for creating JSON responses.
 */
interface ResponseFactory
{
    /**
     * Generate a JSON response.
     *
     * @param  array $data
     * @param  int   $status
     * @param  array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    public function make(array $data, int $status, array $headers = []): JsonResponse;
}