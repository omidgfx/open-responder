<?php

namespace Omidgfx\Responder\Transformers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Provides relationship wiring for transformers.
 *
 * Declares two configuration arrays on the consumer:
 *   - $relations : relations that are available to be included on request.
 *   - $load      : relations that are always (eager) loaded by default.
 * Each entry may map a relation name to a transformer class for nested
 * relationships, or to a callable serving as a query constraint.
 *
 * The trait also supports per-relation `loadFoo` and `filterFoo` methods:
 *   - loadFoo($query)      applies a query constraint when eager loading.
 *   - filterFoo($relation) filters relationship output after the fact.
 */
trait HasRelationships
{
    /** @var array<string, mixed> */
    protected $relations = [];

    /** @var array<string, mixed> */
    protected $load = [];

    /**
     * Return the requested relations that are whitelisted on the transformer,
     * including any nested relations beneath them.
     *
     * @param  array  $requested
     * @return array
     */
    public function relations(array $requested = []): array
    {
        $requested = $this->normalizeRelations($requested);
        $relations = $this->applyQueryConstraints($this->extractRelations($requested));
        $nestedRelations = $this->nestedRelations($requested, $relations, 'relations');

        return array_merge($relations, $nestedRelations);
    }

    /**
     * Return the default (autoloaded) relations, plus any nested relations.
     *
     * @param  array  $requested
     * @return array
     */
    public function defaultRelations(array $requested = []): array
    {
        $requested = $this->normalizeRelations($requested);
        $relations = $this->applyQueryConstraints($this->normalizeRelations($this->load));
        $nestedRelations = $this->nestedRelations($relations, array_merge($relations, $requested), 'defaultRelations');

        return array_merge($relations, $nestedRelations);
    }

    /**
     * Normalize the union of whitelisted and autoloaded relations.
     *
     * @return array
     */
    protected function availableRelations(): array
    {
        return $this->normalizeRelations(array_merge($this->relations, $this->load));
    }

    /**
     * Walk the transformers mapped to the available relations and recurse into
     * their own relation methods for any child relations that were requested.
     *
     * @param  array  $requested
     * @param  array  $available
     * @param  string  $method
     * @return array
     */
    protected function nestedRelations(array $requested, array $available, string $method): array
    {
        $transformers = $this->mappedTransformers($available);

        return collect(array_keys($transformers))->reduce(function ($nestedRelations, $relation) use ($requested, $method, $transformers) {
            $transformer = $transformers[$relation];
            $children = $this->extractChildRelations($requested, $relation);
            $childRelations = $this->wrapChildRelations($transformer->$method($children), $relation);

            return array_merge($nestedRelations, $childRelations);
        }, []);
    }

    /**
     * Keep only requested root relations that exist on the transformer.
     *
     * @param  array  $relations
     * @return array
     */
    protected function extractRelations(array $relations): array
    {
        $available = $this->availableRelations();

        return array_filter($this->mapRelations($relations, function ($relation, $constraint) {
            $identifier = explode('.', $relation)[0];
            $constraint = $identifier === $relation ? $constraint : null;

            return [$identifier => $constraint ?: $this->resolveQueryConstraint($identifier)];
        }), function ($relation) use ($available) {
            return Arr::has($available, explode(':', $relation)[0]);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Pull the child relation segments that sit under the given identifier.
     *
     * @param  array  $relations
     * @param  string  $identifier
     * @return array
     */
    protected function extractChildRelations(array $relations, string $identifier): array
    {
        return array_reduce(array_keys($relations), function ($nested, $relation) use ($relations, $identifier) {
            if (! Str::startsWith($relation, "$identifier.")) {
                return $nested;
            }

            $nestedIdentifier = explode('.', $relation);
            array_shift($nestedIdentifier);

            return array_merge($nested, [implode('.', $nestedIdentifier) => $relations[$relation]]);
        }, []);
    }

    /**
     * Prefix each nested relation identifier with the parent relation.
     *
     * @param  array  $nestedRelations
     * @param  string  $relation
     * @return array
     */
    protected function wrapChildRelations(array $nestedRelations, string $relation): array
    {
        return $this->mapRelations($nestedRelations, function ($nestedRelation, $constraint) use ($relation) {
            return ["$relation.$nestedRelation" => $constraint];
        });
    }

    /**
     * Force a [identifier => constraint/transformer] key/value structure.
     *
     * @param  array  $relations
     * @return array
     */
    protected function normalizeRelations(array $relations): array
    {
        return array_reduce(array_keys($relations), function ($normalized, $relation) use ($relations) {
            if (is_numeric($relation)) {
                return array_merge($normalized, [$relations[$relation] => null]);
            }

            return array_merge($normalized, [$relation => $relations[$relation]]);
        }, []);
    }

    /**
     * Map over a key/value relation list with the given callback.
     *
     * @param  array  $relations
     * @param  callable  $callback
     * @return array
     */
    protected function mapRelations(array $relations, callable $callback): array
    {
        $mapped = [];

        foreach ($relations as $identifier => $value) {
            $mapped = array_merge($mapped, $callback($identifier, $value));
        }

        return $mapped;
    }

    /**
     * Resolve the query constraint for each relation, defaulting to the
     * transformer's `loadFoo` method when one exists.
     *
     * @param  array  $relations
     * @return array
     */
    protected function applyQueryConstraints(array $relations): array
    {
        return $this->mapRelations($relations, function ($relation, $constraint) {
            return [$relation => is_callable($constraint) ? $constraint : $this->resolveQueryConstraint($relation)];
        });
    }

    /**
     * Build the closure that constrains the eager-loading query for a relation.
     *
     * @param  string  $identifier
     * @return \Closure|null
     */
    protected function resolveQueryConstraint(string $identifier)
    {
        if (config('responder.use_camel_case_relations')) {
            $identifier = Str::camel($identifier);
        }

        if (! method_exists($this, $method = 'load'.ucfirst($identifier))) {
            return null;
        }

        return function ($query) use ($method) {
            return $this->$method($query);
        };
    }

    /**
     * Resolve a relation off a model, applying a `filterFoo` method if present.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $identifier
     * @return mixed
     */
    protected function resolveRelation(Model $model, string $identifier)
    {
        if (config('responder.use_camel_case_relations')) {
            $identifier = Str::camel($identifier);
        }

        $relation = $model->$identifier;

        if (method_exists($this, $method = 'filter'.ucfirst($identifier))) {
            return $this->$method($relation);
        }

        return $relation;
    }

    /**
     * Resolve the transformer instances mapped to the given relations.
     *
     * @param  array  $relations
     * @return array
     */
    protected function mappedTransformers(array $relations): array
    {
        $transformers = collect($this->availableRelations())->filter(function ($transformer) {
            return ! is_null($transformer);
        })->map(function ($transformer) {
            return $this->resolveTransformer($transformer);
        })->all();

        return array_intersect_key($transformers, $relations);
    }

    /**
     * Get the transformer class mapped to a relation identifier.
     *
     * @param  string  $identifier
     * @return string|null
     */
    protected function mappedTransformerClass(string $identifier)
    {
        return $this->availableRelations()[$identifier] ?? null;
    }

    /**
     * Resolve a transformer from a class name string.
     *
     * @param  string  $transformer
     * @return mixed
     */
    abstract protected function resolveTransformer(string $transformer);
}
