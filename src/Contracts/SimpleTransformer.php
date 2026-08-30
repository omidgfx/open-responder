<?php

namespace Omidgfx\Responder\Contracts;

use Omidgfx\Responder\TransformBuilder;

/**
 * A contract for transforming data, without the serializing.
 */
interface SimpleTransformer
{
    /**
     * Transform the data without serializing, using the given transformer.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|callable|string|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\TransformBuilder
     */
    public function make($data = null, $transformer = null, ?string $resourceKey = null): TransformBuilder;
}
