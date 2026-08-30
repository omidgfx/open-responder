<?php

namespace Omidgfx\Responder\Transformers;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Pass-through transformer that returns the given data as an array,
 * converting [Arrayable] objects along the way.
 */
class ArrayTransformer extends Transformer
{
    /**
     * Transform the data.
     *
     * @param  mixed  $data
     * @return array
     */
    public function transform($data)
    {
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }
}
