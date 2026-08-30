<?php

namespace Omidgfx\Responder\Transformers;

use Illuminate\Contracts\Container\Container;
use Omidgfx\Responder\Contracts\Transformable;
use Omidgfx\Responder\Contracts\Transformers\TransformerResolver as TransformerResolverContract;
use Omidgfx\Responder\Exceptions\InvalidTransformerException;
use Traversable;

/**
 * Resolves the transformer for a given piece of data.
 *
 * Resolution order is:
 *  1. an explicit binding registered for the data's class,
 *  2. a transformer returned by a [Transformable] object,
 *  3. the configured fallback transformer.
 */
class TransformerResolver implements TransformerResolverContract
{
    /** @var array Class name → transformer mapping. */
    protected $bindings = [];

    /** @var Container */
    protected $container;

    /** @var \Omidgfx\Responder\Transformers\Transformer|string|callable */
    protected $fallback;

    /**
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable  $fallback
     */
    public function __construct(Container $container, $fallback)
    {
        $this->container = $container;
        $this->fallback = $fallback;
    }

    /**
     * Register one or more class → transformer bindings.
     *
     * @param  string|array  $transformable
     * @param  string|callable|null  $transformer
     * @return void
     */
    public function bind($transformable, $transformer = null)
    {
        $this->bindings = array_merge($this->bindings, is_array($transformable) ? $transformable : [
            $transformable => $transformer,
        ]);
    }

    /**
     * Resolve a transformer from a value, class name or callable.
     *
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable  $transformer
     * @return \Omidgfx\Responder\Transformers\Transformer|callable
     */
    public function resolve($transformer)
    {
        if (is_string($transformer)) {
            return $this->container->make($transformer);
        }

        if (! is_callable($transformer) && ! $transformer instanceof Transformer) {
            throw new InvalidTransformerException;
        }

        return $transformer;
    }

    /**
     * Resolve a transformer using the given data.
     *
     * @param  mixed  $data
     * @return \Omidgfx\Responder\Transformers\Transformer|callable
     */
    public function resolveFromData($data)
    {
        $transformer = $this->resolveTransformer($this->resolveTransformableItem($data));

        return $this->resolve($transformer);
    }

    /**
     * Look up the transformer registered for the given transformable element.
     *
     * @param  mixed  $transformable
     * @return \Omidgfx\Responder\Contracts\Transformable|callable
     */
    protected function resolveTransformer($transformable)
    {
        if (is_object($transformable) && key_exists(get_class($transformable), $this->bindings)) {
            return $this->bindings[get_class($transformable)];
        }

        if ($transformable instanceof Transformable) {
            return $transformable->transformer();
        }

        return $this->resolve($this->fallback);
    }

    /**
     * Extract a single transformable element from an iterable data set.
     *
     * @param  mixed  $data
     * @return mixed
     */
    protected function resolveTransformableItem($data)
    {
        if (is_array($data) || $data instanceof Traversable) {
            foreach ($data as $item) {
                return $item;
            }
        }

        return $data;
    }
}
