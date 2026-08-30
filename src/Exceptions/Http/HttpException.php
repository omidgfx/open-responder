<?php

namespace Omidgfx\Responder\Exceptions\Http;

use Symfony\Component\HttpKernel\Exception\HttpException as BaseHttpException;

/**
 * Base class for all API error responses.
 *
 * Concrete subclasses set the default HTTP status, error code and response
 * message. These are consumed by the responder when the exception is rendered.
 */
abstract class HttpException extends BaseHttpException
{
    /** @var int */
    protected $status = 500;

    /** @var string|null */
    protected $errorCode = null;

    /** @var string */
    protected $message = '';

    /** @var array|null */
    protected $data = null;

    /** @var array */
    protected $headers = [];

    /**
     * @param  string|null  $message
     * @param  array|null  $headers
     */
    public function __construct(?string $message = null, ?array $headers = null)
    {
        parent::__construct($this->status, $message ?? $this->message, null, $headers ?? $this->headers);
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->status;
    }

    /**
     * Get the error code.
     *
     * @return string|null
     */
    public function errorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get the error message.
     *
     * @return string|null
     */
    public function message()
    {
        return $this->message ?: null;
    }

    /**
     * Get any additional error data.
     *
     * @return array|null
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Get the attached headers.
     *
     * @return array|null
     */
    public function headers()
    {
        return $this->headers;
    }
}
