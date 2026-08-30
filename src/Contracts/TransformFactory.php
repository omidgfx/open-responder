<?php

namespace Omidgfx\Responder\Contracts;

use Omidgfx\Responder\Fractal\Resource\ResourceInterface;
use Omidgfx\Responder\Fractal\Serializer\SerializerAbstract;

/**
 * A contract for transforming and serializing data.
 */
interface TransformFactory
{
    /**
     * Transform the given resource, and serialize the data with the given serializer.
     *
     * @param  \Omidgfx\Responder\Fractal\Resource\ResourceInterface    $resource
     * @param  \Omidgfx\Responder\Fractal\Serializer\SerializerAbstract $serializer
     * @param  array                                         $options
     * @return array|null
     */
    public function make(ResourceInterface $resource, SerializerAbstract $serializer, array $options = []);
}