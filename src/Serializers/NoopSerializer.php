<?php

namespace Omidgfx\Responder\Serializers;

use Omidgfx\Responder\Fractal\Pagination\CursorInterface;
use Omidgfx\Responder\Fractal\Pagination\PaginatorInterface;

/**
 * Returns the transformed data untouched, dropping the `data` wrapper, meta,
 * pagination and cursor output. Used internally by the [Transformation] class
 * for non-serializing transforms.
 */
class NoopSerializer extends SuccessSerializer
{
    /**
     * Serialize collection resources.
     *
     * @param  string  $resourceKey
     * @param  array  $data
     * @return array
     */
    public function collection($resourceKey, array $data): array
    {
        return $data;
    }

    /**
     * Serialize item resources.
     *
     * @param  string  $resourceKey
     * @param  array  $data
     * @return array
     */
    public function item($resourceKey, array $data): array
    {
        return $data;
    }

    /**
     * Serialize null resources.
     *
     * @return array|null
     */
    public function null(): ?array
    {
        return null;
    }

    /**
     * Format meta data.
     *
     * @param  array  $meta
     * @return array
     */
    public function meta(array $meta): array
    {
        return [];
    }

    /**
     * Format pagination data.
     *
     * @param  \Omidgfx\Responder\Fractal\Pagination\PaginatorInterface  $paginator
     * @return array
     */
    public function paginator(PaginatorInterface $paginator): array
    {
        return [];
    }

    /**
     * Format cursor data.
     *
     * @param  \Omidgfx\Responder\Fractal\Pagination\CursorInterface  $cursor
     * @return array
     */
    public function cursor(CursorInterface $cursor): array
    {
        return [];
    }

    /**
     * Merge includes into the transformed data.
     *
     * @param  array  $transformedData
     * @param  array  $includedData
     * @return array
     */
    public function mergeIncludes($transformedData, $includedData): array
    {
        return array_merge($transformedData, $includedData);
    }
}
