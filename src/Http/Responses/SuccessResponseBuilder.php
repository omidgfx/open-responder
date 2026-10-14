<?php

namespace Omidgfx\Responder\Http\Responses;

use BadMethodCallException;
use InvalidArgumentException;
use Omidgfx\Responder\Contracts\ResponseFactory;
use Omidgfx\Responder\Fractal\Pagination\Cursor;
use Omidgfx\Responder\Fractal\Pagination\IlluminatePaginatorAdapter;
use Omidgfx\Responder\Fractal\Serializer\SerializerAbstract;
use Omidgfx\Responder\TransformBuilder;

/**
 * Responsible for assembling a successful JSON response.
 *
 * Data is handed to the shared [TransformBuilder], whose own fluent methods
 * (meta / with / without / only / serializer / paginator / cursor) are
 * proxied through this builder via [__call].
 *
 * @method \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder meta(array $meta)
 * @method \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder context(string|array $key, mixed $value = null)
 * @method \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder with(array|string $relations)
 * @method \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder without(array|string $relations)
 * @method \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder serializer(SerializerAbstract|string $serializer)
 * @method \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder paginator(IlluminatePaginatorAdapter $paginator)
 * @method \Omidgfx\Responder\Http\Responses\SuccessResponseBuilder cursor(Cursor $cursor)
 */
class SuccessResponseBuilder extends ResponseBuilder
{
    /** @var TransformBuilder */
    protected $transformBuilder;

    /** @var int */
    protected $status = 200;

    /**
     * @param  ResponseFactory  $responseFactory
     * @param  TransformBuilder  $transformBuilder
     */
    public function __construct(ResponseFactory $responseFactory, TransformBuilder $transformBuilder)
    {
        $this->transformBuilder = $transformBuilder;

        parent::__construct($responseFactory);
    }

    /**
     * Supply the resource data, transformer and resource key for the payload.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|callable|string|null  $transformer
     * @param  string|null  $resourceKey
     * @return $this
     */
    public function transform($data = null, $transformer = null, ?string $resourceKey = null): SuccessResponseBuilder
    {
        $this->transformBuilder->resource($data, $transformer, $resourceKey);

        return $this;
    }

    /**
     * Proxy known builder calls through to the transform builder.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (in_array($name, ['cursor', 'paginator', 'meta', 'with', 'without', 'only', 'serializer', 'context'])) {
            $this->transformBuilder->$name(...$arguments);

            return $this;
        }

        throw new BadMethodCallException;
    }

    /**
     * Delegate rendering to the transform builder.
     *
     * @return array
     */
    protected function getOutput(): array
    {
        return $this->transformBuilder->transform();
    }

    /**
     * Reject status codes that are not a successful (2xx/3xx) range.
     *
     * @param  int  $status
     * @return void
     */
    protected function validateStatusCode(int $status)
    {
        if ($status < 100 || $status >= 400) {
            throw new InvalidArgumentException("{$status} is not a valid success HTTP status code.");
        }
    }
}
