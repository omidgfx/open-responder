<?php

namespace Omidgfx\Responder\Resources;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Omidgfx\Responder\Pagination\CursorPaginator;

/**
 * Normalizes a variety of data types (queries, paginators and relationships)
 * down to plain collections or models before a resource is created.
 */
class DataNormalizer
{
    /**
     * Normalize the given data.
     *
     * @param  mixed  $data
     * @return mixed
     */
    public function normalize($data = null)
    {
        if ($this->isInstanceOf($data, [Builder::class, EloquentBuilder::class, CursorPaginator::class])) {
            return $data->get();
        } elseif ($data instanceof Paginator) {
            return $data->getCollection();
        } elseif ($data instanceof Relation) {
            return $this->normalizeRelation($data);
        }

        return $data;
    }

    /**
     * Normalize an Eloquent relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    protected function normalizeRelation(Relation $relation)
    {
        if ($this->isInstanceOf($relation, [BelongsTo::class, HasOne::class, MorphOne::class, MorphTo::class])) {
            return $relation->first();
        }

        return $relation->get();
    }

    /**
     * Whether the data matches any of the given classes.
     *
     * @param  mixed  $data
     * @param  array  $classes
     * @return bool
     */
    protected function isInstanceOf($data, array $classes): bool
    {
        foreach ($classes as $class) {
            if ($data instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
