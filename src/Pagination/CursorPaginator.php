<?php

namespace Omidgfx\Responder\Pagination;

use Closure;
use Illuminate\Support\Collection;
use LogicException;

/**
 * A lightweight cursor-based paginator holding a collection of items together
 * with the current / previous / next cursors.
 */
class CursorPaginator
{
    /** @var \Illuminate\Support\Collection */
    protected $items;

    /** @var int|string|null */
    protected $cursor;

    /** @var int|string|null */
    protected $previousCursor;

    /** @var int|string|null */
    protected $nextCursor;

    /** @var \Closure|null */
    protected static $currentCursorResolver;

    /**
     * @param  \Illuminate\Support\Collection|array|null  $data
     * @param  int|string|null  $cursor
     * @param  int|string|null  $previousCursor
     * @param  int|string|null  $nextCursor
     */
    public function __construct($data, $cursor, $previousCursor, $nextCursor)
    {
        $this->cursor = $cursor;
        $this->previousCursor = $previousCursor;
        $this->nextCursor = $nextCursor;

        $this->set($data);
    }

    /**
     * Retrieve the current cursor reference.
     *
     * @return int|string|null
     */
    public function cursor()
    {
        return $this->cursor;
    }

    /**
     * Retrieve the previous cursor reference.
     *
     * @return int|string|null
     */
    public function previous()
    {
        return $this->previousCursor;
    }

    /**
     * Retrieve the next cursor reference.
     *
     * @return int|string|null
     */
    public function next()
    {
        return $this->nextCursor;
    }

    /**
     * Get the slice of items being paginated.
     *
     * @return array
     */
    public function items(): array
    {
        return $this->items->all();
    }

    /**
     * Get the paginator's underlying collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(): Collection
    {
        return $this->items;
    }

    /**
     * Set the paginator's underlying collection.
     *
     * @param  \Illuminate\Support\Collection|array|null  $data
     * @return $this
     */
    public function set($data): CursorPaginator
    {
        $this->items = $data instanceof Collection ? $data : collect($data);

        return $this;
    }

    /**
     * Resolve the current cursor using the resolver callback.
     *
     * @param  string  $name
     * @return mixed
     *
     * @throws \LogicException
     */
    public static function resolveCursor(string $name = 'cursor')
    {
        if (isset(static::$currentCursorResolver)) {
            return call_user_func(static::$currentCursorResolver, $name);
        }

        throw new LogicException("Could not resolve cursor with the name [$name].");
    }

    /**
     * Set the current cursor resolver callback.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function cursorResolver(Closure $resolver)
    {
        static::$currentCursorResolver = $resolver;
    }
}
