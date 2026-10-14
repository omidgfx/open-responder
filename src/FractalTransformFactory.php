<?php

namespace Omidgfx\Responder;

use LogicException;
use Omidgfx\Responder\Contracts\TransformFactory;
use Omidgfx\Responder\Fractal\Manager;
use Omidgfx\Responder\Fractal\Resource\NullResource;
use Omidgfx\Responder\Fractal\Resource\ResourceInterface;
use Omidgfx\Responder\Fractal\Serializer\SerializerAbstract;

/**
 * Default [TransformFactory] implementation, backed by the bundled Fractal
 * engine. It wires the manager up with the requested includes, excludes and
 * sparse fieldsets, then triggers the serialization.
 */
class FractalTransformFactory implements TransformFactory
{
    /** @var Manager */
    protected $manager;

    /**
     * @param  \Omidgfx\Responder\Fractal\Manager  $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Transform and serialize the given resource.
     *
     * @param  \Omidgfx\Responder\Fractal\Resource\ResourceInterface  $resource
     * @param  \Omidgfx\Responder\Fractal\Serializer\SerializerAbstract  $serializer
     * @param  array  $options
     * @return array|null
     */
    public function make(ResourceInterface $resource, SerializerAbstract $serializer, array $options = [])
    {
        $options = $this->parseOptions($options, $resource);

        return $this->manager->setSerializer($serializer)
            ->setContext($options['context'])
            ->parseIncludes($options['includes'])
            ->parseExcludes($options['excludes'])
            ->parseFieldsets($options['fieldsets'])
            ->createData($resource)
            ->toArray();
    }

    /**
     * Normalize the option bag and, when sparse fieldsets are present, rewrite
     * them into the resource-keyed form the manager expects.
     *
     * @param  array  $options
     * @param  \Omidgfx\Responder\Fractal\Resource\ResourceInterface  $resource
     * @return array
     */
    protected function parseOptions(array $options, ResourceInterface $resource): array
    {
        $options = array_merge([
            'includes' => [],
            'excludes' => [],
            'fieldsets' => [],
            'context' => [],
        ], $options);

        if (! empty($options['fieldsets']) && ! $resource instanceof NullResource) {
            if (empty($resourceKey = $resource->getResourceKey())) {
                throw new LogicException('Filtering fields using sparse fieldsets require resource key to be set.');
            }

            $options['fieldsets'] = $this->parseFieldsets($options['fieldsets'], $resourceKey, $options['includes']);
        }

        return $options;
    }

    /**
     * Convert a flat list of fieldsets into a resource-keyed map of comma
     * separated field strings, pulling in any related resource keys required by
     * the included relations.
     *
     * @param  array  $fieldsets
     * @param  string  $resourceKey
     * @param  array  $includes
     * @return array
     */
    protected function parseFieldsets(array $fieldsets, string $resourceKey, array $includes): array
    {
        $includes = array_map(function ($include) use ($resourceKey) {
            return "$resourceKey.$include";
        }, $includes);

        foreach ($fieldsets as $key => $fields) {
            if (is_numeric($key)) {
                unset($fieldsets[$key]);
                $key = $resourceKey;
            }

            $fields = $this->parseFieldset($key, (array) $fields, $includes);
            $fieldsets[$key] = array_unique(array_merge(key_exists($key, $fieldsets) ? (array) $fieldsets[$key] : [], $fields));
        }

        return array_map(function ($fields) {
            return implode(',', $fields);
        }, $fieldsets);
    }

    /**
     * Merge explicitly requested fields with those that the included relations
     * imply should be present for a given resource key.
     *
     * @param  string  $key
     * @param  array  $fields
     * @param  array  $includes
     * @return array
     */
    protected function parseFieldset(string $key, array $fields, array $includes): array
    {
        $childIncludes = array_reduce($includes, function ($segments, $include) use ($key) {
            return array_merge($segments, $this->resolveChildIncludes($key, $include));
        }, []);

        return array_merge($fields, array_unique($childIncludes));
    }

    /**
     * Determine the direct children of the given resource key within a dotted
     * include path. Returns an empty array when the key is not an ancestor.
     *
     * @param  string  $key
     * @param  string  $include
     * @return array
     */
    protected function resolveChildIncludes($key, string $include): array
    {
        if (count($segments = explode('.', $include)) <= 1) {
            return [];
        }

        $relation = $key === array_shift($segments) ? [$segments[0]] : [];

        return array_merge($relation, $this->resolveChildIncludes($key, implode('.', $segments)));
    }
}
