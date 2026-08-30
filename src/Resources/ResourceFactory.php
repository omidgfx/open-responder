<?php

namespace Omidgfx\Responder\Resources;

use Illuminate\Support\Arr;
use Omidgfx\Responder\Contracts\Resources\ResourceFactory as ResourceFactoryContract;
use Omidgfx\Responder\Contracts\Resources\ResourceKeyResolver as ResourceKeyResolverContract;
use Omidgfx\Responder\Contracts\Transformers\TransformerResolver;
use Omidgfx\Responder\Fractal\Resource\Collection as CollectionResource;
use Omidgfx\Responder\Fractal\Resource\Item as ItemResource;
use Omidgfx\Responder\Fractal\Resource\NullResource;
use Omidgfx\Responder\Fractal\Resource\Primitive;
use Omidgfx\Responder\Fractal\Resource\ResourceInterface;
use Traversable;

/**
 * Builds the correct Fractal resource type for a given set of data and resolves
 * the transformer and resource key to attach to it.
 */
class ResourceFactory implements ResourceFactoryContract
{
    /** @var DataNormalizer */
    protected $normalizer;

    /** @var \Omidgfx\Responder\Contracts\Transformers\TransformerResolver */
    protected $transformerResolver;

    /** @var \Omidgfx\Responder\Contracts\Resources\ResourceKeyResolver */
    protected $resourceKeyResolver;

    /**
     * @param  DataNormalizer  $normalizer
     * @param  \Omidgfx\Responder\Contracts\Transformers\TransformerResolver  $transformerResolver
     * @param  \Omidgfx\Responder\Contracts\Resources\ResourceKeyResolver  $resourceKeyResolver
     */
    public function __construct(DataNormalizer $normalizer, TransformerResolver $transformerResolver, ResourceKeyResolverContract $resourceKeyResolver)
    {
        $this->normalizer = $normalizer;
        $this->transformerResolver = $transformerResolver;
        $this->resourceKeyResolver = $resourceKeyResolver;
    }

    /**
     * Make a resource from the given data.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    public function make($data = null, $transformer = null, ?string $resourceKey = null): ResourceInterface
    {
        if ($data instanceof ResourceInterface) {
            return $this->makeFromResource($data, $transformer, $resourceKey);
        } elseif (is_null($data = $this->normalizer->normalize($data))) {
            return $this->instatiateResource($data, null, $resourceKey);
        }

        $transformer = $this->resolveTransformer($data, $transformer);
        $resourceKey = $this->resolveResourceKey($data, $resourceKey);

        return $this->instatiateResource($data, $transformer, $resourceKey);
    }

    /**
     * Re-attach a resolved transformer and resource key onto an existing resource.
     *
     * @param  \Omidgfx\Responder\Fractal\Resource\ResourceInterface  $resource
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    public function makeFromResource(ResourceInterface $resource, $transformer = null, ?string $resourceKey = null): ResourceInterface
    {
        $transformer = $this->resolveTransformer($resource->getData(), $transformer ?: $resource->getTransformer());
        $resourceKey = $this->resolveResourceKey($resource->getData(), $resourceKey ?: $resource->getResourceKey());

        return $resource->setTransformer($transformer)->setResourceKey($resourceKey);
    }

    /**
     * Instantiate the appropriate resource type for the data.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|callable|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    protected function instatiateResource($data, $transformer = null, ?string $resourceKey = null): ResourceInterface
    {
        if (is_null($data)) {
            return new NullResource(null, null, $resourceKey);
        } elseif ($this->shouldCreateCollection($data)) {
            return new CollectionResource($data, $transformer, $resourceKey);
        } elseif (is_scalar($data)) {
            return new Primitive($data, $transformer, $resourceKey);
        }

        return new ItemResource($data, $transformer, $resourceKey);
    }

    /**
     * Whether the data should be wrapped in a collection resource.
     *
     * @param  mixed  $data
     * @return bool
     */
    protected function shouldCreateCollection($data): bool
    {
        if (is_array($data)) {
            return ! Arr::isAssoc($data) && ! is_scalar(Arr::first($data));
        }

        return $data instanceof Traversable;
    }

    /**
     * Resolve the transformer, either from an explicit value or from the data.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable|null  $transformer
     * @return \Omidgfx\Responder\Transformers\Transformer|callable
     */
    protected function resolveTransformer($data, $transformer)
    {
        if (isset($transformer)) {
            return $this->transformerResolver->resolve($transformer);
        }

        return $this->transformerResolver->resolveFromData($data);
    }

    /**
     * Resolve the resource key, falling back to the key resolver.
     *
     * @param  mixed  $data
     * @param  string|null  $resourceKey
     * @return null|string
     */
    protected function resolveResourceKey($data, ?string $resourceKey = null)
    {
        return ! empty($resourceKey) ? $resourceKey : $this->resourceKeyResolver->resolve($data);
    }
}
