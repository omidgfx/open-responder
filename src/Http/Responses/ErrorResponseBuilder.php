<?php

namespace Omidgfx\Responder\Http\Responses;

use InvalidArgumentException;
use Omidgfx\Responder\Contracts\ErrorFactory;
use Omidgfx\Responder\Contracts\ErrorSerializer;
use Omidgfx\Responder\Contracts\ResponseFactory;
use Omidgfx\Responder\Exceptions\InvalidErrorSerializerException;

/**
 * Responsible for assembling a structured error (4xx/5xx) JSON response.
 *
 * The error message may be resolved automatically from the error code via the
 * error factory when no explicit message is provided.
 */
class ErrorResponseBuilder extends ResponseBuilder
{
    /** @var ErrorFactory */
    private $errorFactory;

    /** @var ErrorSerializer */
    protected $serializer;

    /** @var string|null */
    protected $errorCode = null;

    /** @var string|null */
    protected $message = null;

    /** @var array|null */
    protected $data = null;

    /** @var int */
    protected $status = 500;

    /**
     * @param  ResponseFactory  $responseFactory
     * @param  ErrorFactory  $errorFactory
     */
    public function __construct(ResponseFactory $responseFactory, ErrorFactory $errorFactory)
    {
        $this->errorFactory = $errorFactory;

        parent::__construct($responseFactory);
    }

    /**
     * Assign the error code and, optionally, a direct message.
     *
     * @param  mixed|null  $errorCode
     * @param  string|null  $message
     * @return $this
     */
    public function error($errorCode = null, ?string $message = null)
    {
        $this->errorCode = $errorCode;
        $this->message = $message;

        return $this;
    }

    /**
     * Merge additional key/value data into the error payload.
     *
     * @param  array|null  $data
     * @return $this
     */
    public function data(?array $data = null)
    {
        $this->data = array_merge((array) $this->data, (array) $data);

        return $this;
    }

    /**
     * Swap the error serializer used to format the payload.
     *
     * @param  \Omidgfx\Responder\Contracts\ErrorSerializer|string  $serializer
     * @return $this
     *
     * @throws \Omidgfx\Responder\Exceptions\InvalidErrorSerializerException
     */
    public function serializer($serializer)
    {
        if (is_string($serializer)) {
            $serializer = new $serializer;
        }

        if (! $serializer instanceof ErrorSerializer) {
            throw new InvalidErrorSerializerException;
        }

        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Let the error factory format the payload.
     *
     * @return array
     */
    protected function getOutput(): array
    {
        return $this->errorFactory->make($this->serializer, $this->errorCode, $this->message, $this->data);
    }

    /**
     * Reject status codes outside the client/server error range.
     *
     * @param  int  $status
     * @return void
     */
    protected function validateStatusCode(int $status)
    {
        if ($status < 400 || $status >= 600) {
            throw new InvalidArgumentException("{$status} is not a valid error HTTP status code.");
        }
    }
}
