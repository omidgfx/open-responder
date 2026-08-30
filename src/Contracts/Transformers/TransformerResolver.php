<?php

namespace Omidgfx\Responder\Contracts\Transformers;

/**
 * A contract for resolving transformers.
 */
interface TransformerResolver
{
    /**
     * Register a transformable to transformer binding.
     *
     * @param  string|array    $transformable
     * @param  string|callback $transformer
     * @return void
     */
    public function bind($transformable, $transformer);

    /**
     * Resolve a transformer.
     *
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable $transformer
     * @return \Omidgfx\Responder\Transformers\Transformer|callable
     * @throws \Omidgfx\Responder\Exceptions\InvalidTransformerException
     */
    public function resolve($transformer);

    /**
     * Resolve a transformer from the given data.
     *
     * @param  mixed $data
     * @return \Omidgfx\Responder\Transformers\Transformer|callable
     */
    public function resolveFromData($data);
}