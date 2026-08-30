<?php

namespace Omidgfx\Responder\Contracts\Pagination;

use Omidgfx\Responder\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Omidgfx\Responder\Fractal\Pagination\Cursor;
use Omidgfx\Responder\Fractal\Pagination\PaginatorInterface;

/**
 * A contract for creating pagination adapters.
 */
interface PaginatorFactory
{
    /**
     * Make a Fractal paginator adapter from a Laravel paginator.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @return \Omidgfx\Responder\Fractal\Pagination\PaginatorInterface
     */
    public function make(LengthAwarePaginator $paginator): PaginatorInterface;

    /**
     * Make a Fractal paginator adapter from a Laravel paginator.
     *
     * @param  \Omidgfx\Responder\Pagination\CursorPaginator $paginator
     * @return \Omidgfx\Responder\Fractal\Pagination\Cursor
     */
    public function makeCursor(CursorPaginator $paginator): Cursor;
}