<?php

namespace Omidgfx\Responder;

use Omidgfx\Responder\Serializers\NoopSerializer;

/**
 * Entry point for transforming data without serializing it into a response.
 * Each call returns a fresh [TransformBuilder] preconfigured with the no-op
 * serializer.
 */
class Transformation
{
    /** @var TransformBuilder */
    protected $transformBuilder;

    /**
     * @param  \Omidgfx\Responder\TransformBuilder  $transformBuilder
     */
    public function __construct(TransformBuilder $transformBuilder)
    {
        $this->transformBuilder = $transformBuilder;
    }

    /**
     * Make a new no-serialization transformation.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|callable|string|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\TransformBuilder
     */
    public function make($data = null, $transformer = null, ?string $resourceKey = null): TransformBuilder
    {
        return $this->transformBuilder->resource($data, $transformer, $resourceKey)->serializer(new NoopSerializer);
    }
}
