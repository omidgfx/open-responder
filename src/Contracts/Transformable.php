<?php

namespace Omidgfx\Responder\Contracts;

/**
 * A contract for making the class transformable.
 */
interface Transformable
{
    /**
     * Get a transformer for the class.
     *
     * @return \Omidgfx\Responder\Transformers\Transformer|callable|string|null
     */
    public function transformer();
}