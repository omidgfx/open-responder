<?php

namespace Omidgfx\Responder\Serializers;

use Omidgfx\Responder\Fractal\Pagination\CursorInterface;
use Omidgfx\Responder\Fractal\Pagination\PaginatorInterface;
use Omidgfx\Responder\Fractal\Serializer\ArraySerializer;

/**
 * Default success serializer. Mirrors the ArraySerializer but wraps output in a
 * `data` key and reshapes pagination and cursor metadata to the responder's
 * own (camel-cased) structure.
 */
class SuccessSerializer extends ArraySerializer
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
        return ['data' => $data];
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
        return ['data' => $data];
    }

    /**
     * Serialize null resources.
     *
     * @return array|null
     */
    public function null(): ?array
    {
        return ['data' => null];
    }

    /**
     * Format meta data.
     *
     * @param  array  $meta
     * @return array
     */
    public function meta(array $meta): array
    {
        return $meta;
    }

    /**
     * Format pagination data.
     *
     * @param  \Omidgfx\Responder\Fractal\Pagination\PaginatorInterface  $paginator
     * @return array
     */
    public function paginator(PaginatorInterface $paginator): array
    {
        $pagination = parent::paginator($paginator)['pagination'];

        return [
            'pagination' => [
                'count' => $pagination['count'],
                'total' => $pagination['total'],
                'perPage' => $pagination['per_page'],
                'currentPage' => $pagination['current_page'],
                'totalPages' => $pagination['total_pages'],
                'links' => $pagination['links'],
            ],
        ];
    }

    /**
     * Format cursor data.
     *
     * @param  \Omidgfx\Responder\Fractal\Pagination\CursorInterface  $cursor
     * @return array
     */
    public function cursor(CursorInterface $cursor): array
    {
        return [
            'cursor' => [
                'current' => $cursor->getCurrent(),
                'previous' => $cursor->getPrev(),
                'next' => $cursor->getNext(),
                'count' => (int) $cursor->getCount(),
            ],
        ];
    }

    /**
     * Merge includes into the transformed data, unwrapping their `data` key.
     *
     * @param  array  $transformedData
     * @param  array  $includedData
     * @return array
     */
    public function mergeIncludes($transformedData, $includedData): array
    {
        foreach (array_keys($includedData) as $key) {
            $includedData[$key] = $includedData[$key]['data'];
        }

        return array_merge($transformedData, $includedData);
    }
}
