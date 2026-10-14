<?php

namespace Omidgfx\Responder;

/**
 * The bag of values a caller hands to a transformation - the situation the
 * transformation runs in. It travels with the manager and down every scope
 * of the resource tree, so nested transformers read the same values the
 * root transformer was given.
 */
class TransformContext
{
    /** @var array */
    protected $values = [];

    /**
     * @param  array  $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * Read a value out of the context.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }

    /**
     * Whether the context carries the given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * Every value in the bag.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * A copy of the context with the given values merged in.
     *
     * @param  array  $values
     * @return static
     */
    public function merge(array $values)
    {
        return new static(array_merge($this->values, $values));
    }
}
