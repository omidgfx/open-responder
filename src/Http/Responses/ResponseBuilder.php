<?php

namespace Omidgfx\Responder\Http\Responses;

use Omidgfx\Responder\Contracts\ResponseFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Base class for success and error response builders. It holds the shared
 * response-factory dependency, handles decorators, and defines the small set
 * of output formats (array / collection / JSON / HTTP response) that a builder
 * can be rendered into.
 */
abstract class ResponseBuilder implements Arrayable, Jsonable
{
    /** @var ResponseFactory */
    protected $responseFactory;

    /** @var int */
    protected $status;

    /**
     * @param  ResponseFactory  $responseFactory
     */
    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Wrap the underlying factory with one or more decorators. Each decorator
     * receives the previous factory and is expected to comply with the
     * [ResponseFactory] contract.
     *
     * @param  string[]|string  $decorator
     * @return $this
     */
    public function decorator($decorator)
    {
        $decorators = is_array($decorator) ? $decorator : func_get_args();

        foreach ($decorators as $decorator) {
            $this->responseFactory = new $decorator($this->responseFactory);
        }

        return $this;
    }

    /**
     * Render the builder into an HTTP JSON response.
     *
     * @param  int|null  $status
     * @param  array  $headers
     * @return \Illuminate\Http\JsonResponse
     */
    public function respond(?int $status = null, array $headers = []): JsonResponse
    {
        if (! is_null($status)) {
            $this->setStatusCode($status);
        }

        return $this->responseFactory->make($this->getOutput(), $this->status, $headers);
    }

    /**
     * Render the builder as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->respond()->getData(true);
    }

    /**
     * Render the builder as a Laravel collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toCollection(): Collection
    {
        return new Collection($this->toArray());
    }

    /**
     * Render the builder as a JSON string.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Assign and validate the status code for the upcoming response.
     *
     * @param  int  $status
     * @return void
     */
    protected function setStatusCode(int $status)
    {
        $this->validateStatusCode($this->status = $status);
    }

    /**
     * Produce the serialized payload pushed into the response factory.
     *
     * @return array
     */
    abstract protected function getOutput(): array;

    /**
     * Guard the allowed range of status codes for the given response type.
     *
     * @param  int  $status
     * @return void
     */
    abstract protected function validateStatusCode(int $status);
}
