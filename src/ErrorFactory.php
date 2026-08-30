<?php

namespace Omidgfx\Responder;

use Omidgfx\Responder\Contracts\ErrorFactory as ErrorFactoryContract;
use Omidgfx\Responder\Contracts\ErrorMessageResolver as ErrorMessageResolverContract;
use Omidgfx\Responder\Contracts\ErrorSerializer;

/**
 * Builds an error array. When an error code is given without a message, the
 * message is resolved through the message resolver, then formatted by the
 * error serializer.
 */
class ErrorFactory implements ErrorFactoryContract
{
    /** @var ErrorMessageResolverContract */
    protected $messageResolver;

    /**
     * @param  \Omidgfx\Responder\Contracts\ErrorMessageResolver  $messageResolver
     */
    public function __construct(ErrorMessageResolverContract $messageResolver)
    {
        $this->messageResolver = $messageResolver;
    }

    /**
     * Build an error array from an error code, message and data.
     *
     * @param  \Omidgfx\Responder\Contracts\ErrorSerializer  $serializer
     * @param  mixed|null  $errorCode
     * @param  string|null  $message
     * @param  array|null  $data
     * @return array
     */
    public function make(ErrorSerializer $serializer, $errorCode = null, ?string $message = null, ?array $data = null): array
    {
        if (isset($errorCode) && ! isset($message)) {
            $message = $this->messageResolver->resolve($errorCode);
        }

        return $serializer->format($errorCode, $message, $data);
    }
}
