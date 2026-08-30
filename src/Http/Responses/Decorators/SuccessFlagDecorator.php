<?php

namespace Omidgfx\Responder\Http\Responses\Decorators;

use Illuminate\Http\JsonResponse;

/**
 * Injects a boolean `success` flag derived from the response status code.
 */
class SuccessFlagDecorator extends ResponseDecorator
{
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
        return $this->factory->make(array_merge([
            'success' => $status >= 100 && $status < 400,
        ], $data), $status, $headers);
    }
}
