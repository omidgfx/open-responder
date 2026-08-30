<?php

namespace Omidgfx\Responder\Contracts\Resources;

use Omidgfx\Responder\Fractal\Resource\ResourceInterface;

/**
 * A contract for creating resources.
 */
interface ResourceFactory
{
    /**
     * Make resource from the given data.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    public function make($data = null, $transformer = null, ?string $resourceKey = null): ResourceInterface;
}
