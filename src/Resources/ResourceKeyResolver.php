<?php

namespace Omidgfx\Responder\Resources;

use Illuminate\Database\Eloquent\Model;
use Omidgfx\Responder\Contracts\Resources\ResourceKeyResolver as ResourceKeyResolverContract;
use Traversable;

/**
 * Determines the resource key (the wrapper key in the serialized payload) for
 * a given piece of data.
 *
 * Resolution order:
 *  1. an explicit binding registered for the data's class,
 *  2. a model that implements `getResourceKey()`,
 *  3. the model's table name,
 *  4. the literal fallback `data`.
 */
class ResourceKeyResolver implements ResourceKeyResolverContract
{
    /** @var array */
    protected $bindings = [];

    /**
     * Register a transformable (class) to resource key binding.
     *
     * @param  string|array  $transformable
     * @param  string  $resourceKey
     * @return void
     */
    public function bind($transformable, string $resourceKey)
    {
        $this->bindings = array_merge($this->bindings, is_array($transformable) ? $transformable : [
            $transformable => $resourceKey,
        ]);
    }

    /**
     * Resolve a resource key from the given data.
     *
     * @param  mixed  $data
     * @return string
     */
    public function resolve($data)
    {
        $transformable = $this->resolveTransformableItem($data);

        if (is_object($transformable) && key_exists(get_class($transformable), $this->bindings)) {
            return $this->bindings[get_class($transformable)];
        }

        if ($transformable instanceof Model) {
            return $this->resolveFromModel($transformable);
        }

        return 'data';
    }

    /**
     * Resolve a resource key for a given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    public function resolveFromModel(Model $model)
    {
        if (method_exists($model, 'getResourceKey')) {
            return $model->getResourceKey();
        }

        return $model->getTable();
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
