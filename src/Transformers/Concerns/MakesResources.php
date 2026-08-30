<?php

namespace Omidgfx\Responder\Transformers\Concerns;

use Countable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;
use Omidgfx\Responder\Contracts\Resources\ResourceFactory;
use Omidgfx\Responder\Fractal\Resource\ResourceInterface;

/**
 * Allows a transformer to build child resources.
 *
 * This is what powers the `includeFoo` convention: an include method may simply
 * return a model / array and the trait wraps it in the appropriate resource,
 * resolved through the resource factory.
 */
trait MakesResources
{
    /** @var \Omidgfx\Responder\Fractal\ResourceInterface[] */
    protected $resources = [];

    /**
     * Build a resource for the given data. Passed-through resources are
     * returned untouched.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable|null  $transformer
     * @param  string|null  $resourceKey
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    protected function resource($data = null, $transformer = null, ?string $resourceKey = null): ResourceInterface
    {
        if ($data instanceof ResourceInterface) {
            return $data;
        }

        $resourceFactory = $this->resolveContainer()->make(ResourceFactory::class);

        return $resourceFactory->make($data, $transformer, $resourceKey);
    }

    /**
     * Include a related resource by its identifier.
     *
     * Prefers an explicit `includeFoo` method; otherwise, when the data is a
     * model, resolves the relation off the model automatically.
     *
     * @param  string  $identifier
     * @param  mixed  $data
     * @param  array  $parameters
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    protected function includeResource(string $identifier, $data, array $parameters): ResourceInterface
    {
        $transformer = $this->mappedTransformerClass($identifier);

        if (config('responder.use_camel_case_relations')) {
            $identifier = Str::camel($identifier);
        }

        if (method_exists($this, $method = 'include'.ucfirst($identifier))) {
            $resource = $this->resource($this->$method($data, collect($parameters)), $transformer, $identifier);
        } elseif ($data instanceof Model) {
            $resource = $this->includeResourceFromModel($data, $identifier, $transformer);
        } else {
            throw new LogicException('Relation ['.$identifier.'] not found in ['.get_class($this).'].');
        }

        return $resource;
    }

    /**
     * Include a model relation, caching the resource so that the same relation
     * can be reused across rows without re-instantiating it.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $identifier
     * @param  \Omidgfx\Responder\Transformers\Transformer|string|callable|null  $transformer
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    protected function includeResourceFromModel(Model $model, string $identifier, $transformer = null): ResourceInterface
    {
        $data = $this->resolveRelation($model, $identifier);

        if (! $this->shouldCacheResource($data)) {
            return $this->resource($data, $transformer, $identifier);
        } elseif (key_exists($identifier, $this->resources)) {
            return $this->resources[$identifier]->setData($data);
        }

        return $this->resources[$identifier] = $this->resource($data, $transformer, $identifier);
    }

    /**
     * Whether a resource (array/countable data or a null relation) is worth
     * caching across rows.
     *
     * @param  mixed  $data
     * @return bool
     */
    protected function shouldCacheResource($data): bool
    {
        return is_array($data) || $data instanceof Countable ? count($data) > 0 : is_null($data);
    }

    /**
     * Resolve a container using the resolver callback.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    abstract protected function resolveContainer(): Container;

    /**
     * Resolve relation data from a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $identifier
     * @return mixed
     */
    abstract protected function resolveRelation(Model $model, string $identifier);

    /**
     * Get a related transformer class mapped to a relation identifier.
     *
     * @param  string  $identifier
     * @return string
     */
    abstract protected function mappedTransformerClass(string $identifier);
}
