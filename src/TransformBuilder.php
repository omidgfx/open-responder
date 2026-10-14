<?php

namespace Omidgfx\Responder;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Omidgfx\Responder\Contracts\Pagination\PaginatorFactory;
use Omidgfx\Responder\Contracts\Resources\ResourceFactory;
use Omidgfx\Responder\Contracts\TransformFactory;
use Omidgfx\Responder\Exceptions\InvalidSuccessSerializerException;
use Omidgfx\Responder\Fractal\Pagination\Cursor;
use Omidgfx\Responder\Fractal\Pagination\IlluminatePaginatorAdapter;
use Omidgfx\Responder\Fractal\Resource\Collection as CollectionResource;
use Omidgfx\Responder\Fractal\Resource\NullResource;
use Omidgfx\Responder\Fractal\Serializer\SerializerAbstract;
use Omidgfx\Responder\Pagination\CursorPaginator;
use Omidgfx\Responder\TransformContext;
use Omidgfx\Responder\Transformers\Transformer;

/**
 * Builds and executes a single transformation: it holds the resource, the
 * serializer, and the requested relations / fieldsets, and finally asks the
 * transform factory to produce a serialized array.
 */
class TransformBuilder
{
    /** @var ResourceFactory */
    protected $resourceFactory;

    /** @var TransformFactory */
    private $transformFactory;

    /** @var PaginatorFactory */
    protected $paginatorFactory;

    /** @var \Omidgfx\Responder\Fractal\Resource\ResourceInterface */
    protected $resource;

    /** @var SerializerAbstract */
    protected $serializer;

    /** @var array */
    protected $with = [];

    /** @var array */
    protected $without = [];

    /** @var array */
    protected $only = [];

    /** @var TransformContext */
    protected $context;

    /**
     * @param  ResourceFactory  $resourceFactory
     * @param  TransformFactory  $transformFactory
     * @param  PaginatorFactory  $paginatorFactory
     */
    public function __construct(ResourceFactory $resourceFactory, TransformFactory $transformFactory, PaginatorFactory $paginatorFactory)
    {
        $this->resourceFactory = $resourceFactory;
        $this->transformFactory = $transformFactory;
        $this->paginatorFactory = $paginatorFactory;
        $this->context = new TransformContext;
    }

    /**
     * Create a resource from the given data / transformer / key, and detect
     * cursor or offset pagination so an adapter can be attached afterwards.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|callable|string|null  $transformer
     * @param  string|null  $resourceKey
     * @return $this
     */
    public function resource($data = null, $transformer = null, ?string $resourceKey = null)
    {
        $this->resource = $this->resourceFactory->make($data, $transformer, $resourceKey);

        if ($data instanceof CursorPaginator) {
            $this->cursor($this->paginatorFactory->makeCursor($data));
        } elseif ($data instanceof LengthAwarePaginator) {
            $this->paginator($this->paginatorFactory->make($data));
        }

        return $this;
    }

    /**
     * Attach a cursor to the resource (only applies to collections).
     *
     * @param  \Omidgfx\Responder\Fractal\Pagination\Cursor  $cursor
     * @return $this
     */
    public function cursor(Cursor $cursor)
    {
        if ($this->resource instanceof CollectionResource) {
            $this->resource->setCursor($cursor);
        }

        return $this;
    }

    /**
     * Attach a paginator adapter to the resource (only applies to collections).
     *
     * @param  \Omidgfx\Responder\Fractal\Pagination\IlluminatePaginatorAdapter  $paginator
     * @return $this
     */
    public function paginator(IlluminatePaginatorAdapter $paginator)
    {
        if ($this->resource instanceof CollectionResource) {
            $this->resource->setPaginator($paginator);
        }

        return $this;
    }

    /**
     * Pass values into the transformers - the situation this transformation
     * runs in: the viewer, a locale, a feature switch, anything the
     * transformer must not compute from the request itself. Accepts a
     * key/value pair or an array of them, and the values flow down to
     * every nested transformer of the resource tree.
     *
     * @param  string|array  $key
     * @param  mixed         $value
     * @return $this
     */
    public function context($key, $value = null)
    {
        $values = is_array($key) ? $key : [$key => $value];

        $this->context = $this->context->merge($values);

        return $this;
    }

    /**
     * Add top-level metadata to the resource.
     *
     * @param  array  $data
     * @return $this
     */
    public function meta(array $data)
    {
        $this->resource->setMeta($data);

        return $this;
    }

    /**
     * Request relations to include, optionally with query constraints.
     * Accepts a string, array, or multiple string arguments.
     *
     * @param  string[]|string  $relations
     * @return $this
     */
    public function with($relations)
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($relations as $relation => $constraint) {
            if (is_numeric($relation)) {
                $relation = $constraint;
                $constraint = null;
            }

            $this->with = array_merge($this->with, [$relation => $constraint]);
        }

        return $this;
    }

    /**
     * Exclude relations from the transformation.
     *
     * @param  string[]|string  $relations
     * @return $this
     */
    public function without($relations)
    {
        $this->without = array_merge($this->without, is_array($relations) ? $relations : func_get_args());

        return $this;
    }

    /**
     * Limit the output to only the given fields (sparse fieldsets).
     *
     * @param  string[]|string  $fields
     * @return $this
     */
    public function only($fields)
    {
        $this->only = array_merge($this->only, is_array($fields) ? $fields : func_get_args());

        return $this;
    }

    /**
     * Set the serializer used to format the output.
     *
     * @param  \Omidgfx\Responder\Fractal\Serializer\SerializerAbstract|string  $serializer
     * @return $this
     *
     * @throws \Omidgfx\Responder\Exceptions\InvalidSuccessSerializerException
     */
    public function serializer($serializer)
    {
        if (is_string($serializer)) {
            $serializer = new $serializer;
        }

        if (! $serializer instanceof SerializerAbstract) {
            throw new InvalidSuccessSerializerException;
        }

        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Execute the transformation and return the serialized array.
     *
     * @return array|null
     */
    public function transform()
    {
        $this->prepareRelations($this->resource->getData(), $this->resource->getTransformer());

        return $this->transformFactory->make($this->resource ?: new NullResource, $this->serializer, [
            'includes' => $this->with,
            'excludes' => $this->without,
            'fieldsets' => $this->only,
            'context'   => $this->context->all(),
        ]);
    }

    /**
     * Merge transformer-defined relations into the requested set and eager load
     * them along with any default relations that are being autoloaded.
     *
     * @param  mixed  $data
     * @param  \Omidgfx\Responder\Transformers\Transformer|callable|string|null  $transformer
     * @return void
     */
    protected function prepareRelations($data, $transformer)
    {
        if ($transformer instanceof Transformer) {
            $relations = $transformer->relations($this->with);
            $defaultRelations = $this->removeExcludedRelations($transformer->defaultRelations($this->with));
            $this->with = array_merge($relations, $defaultRelations);
        }

        if ($data instanceof Model || $data instanceof Collection) {
            $this->eagerLoadRelations($data, $this->with, $transformer);
        }

        $this->with = array_keys($this->with);
    }

    /**
     * Drop relations that the caller has explicitly excluded with [without].
     *
     * @param  array  $relations
     * @return array
     */
    protected function removeExcludedRelations(array $relations): array
    {
        return array_filter($relations, function ($relation) {
            return ! in_array($this->stripParametersFromRelation($relation), $this->without);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Strip the parameter suffix (`relation:params`) from a relation name.
     *
     * @param  string  $relation
     * @return string
     */
    protected function stripParametersFromRelation(string $relation): string
    {
        return explode(':', $relation)[0];
    }

    /**
     * Eager load the given relations on the model / collection, skipping any
     * that are handled by an `includeX` method on the transformer, normalizing
     * camel-casing and turning null constraints into no-op closures.
     *
     * @param  mixed  $data
     * @param  array  $requested
     * @param  \Omidgfx\Responder\Transformers\Transformer|callable|string|null  $transformer
     * @return void
     */
    protected function eagerLoadRelations($data, array $requested, $transformer)
    {
        $relations = collect(array_keys($requested))->reduce(function ($eagerLoads, $relation) use ($requested, $transformer) {
            $identifier = $this->stripParametersFromRelation($relation);

            if (config('responder.use_camel_case_relations')) {
                $identifier = Str::camel($identifier);
            }

            if (method_exists($transformer, 'include'.ucfirst($identifier))) {
                return $eagerLoads;
            }

            return array_merge($eagerLoads, [$identifier => $requested[$relation] ?: function () {
            }]);
        }, []);

        $data->loadMissing($relations);
    }
}
