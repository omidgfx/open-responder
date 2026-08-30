<?php

namespace Omidgfx\Responder\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Omidgfx\Responder\Contracts\Pagination\PaginatorFactory as PaginatorFactoryContract;
use Omidgfx\Responder\Fractal\Pagination\Cursor;
use Omidgfx\Responder\Fractal\Pagination\IlluminatePaginatorAdapter;
use Omidgfx\Responder\Fractal\Pagination\PaginatorInterface;

/**
 * Creates Fractal paginator adapters and cursors from Laravel paginators.
 */
class PaginatorFactory implements PaginatorFactoryContract
{
    /** @var array */
    protected $parameters;

    /**
     * @param  array  $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Make a Fractal paginator adapter from a Laravel paginator.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @return \Omidgfx\Responder\Fractal\Pagination\PaginatorInterface
     */
    public function make(LengthAwarePaginator $paginator): PaginatorInterface
    {
        $paginator->appends($this->parameters);

        return new IlluminatePaginatorAdapter($paginator);
    }

    /**
     * Make a Fractal cursor from a [CursorPaginator].
     *
     * @param  \Omidgfx\Responder\Pagination\CursorPaginator  $paginator
     * @return \Omidgfx\Responder\Fractal\Pagination\Cursor
     */
    public function makeCursor(CursorPaginator $paginator): Cursor
    {
        return new Cursor($paginator->cursor(), $paginator->previous(), $paginator->next(), $paginator->get()->count());
    }
}
