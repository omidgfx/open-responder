<?php

namespace Omidgfx\Responder\Serializers;

use Omidgfx\Responder\Contracts\ErrorSerializer as ErrorSerializerContract;

/**
 * Default error serializer, producing the `error` wrapper with code and message
 * and merging any additional data underneath it.
 */
class ErrorSerializer implements ErrorSerializerContract
{
    /**
     * Format the error data.
     *
     * @param  mixed|null  $errorCode
     * @param  string|null  $message
     * @param  array|null  $data
     * @return array
     */
    public function format($errorCode = null, ?string $message = null, ?array $data = null): array
    {
        $response = [
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ];

        if (is_array($data)) {
            $response['error'] = array_merge($response['error'], $data);
        }

        return $response;
    }
}
