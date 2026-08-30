<?php

namespace Omidgfx\Responder\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Illuminate\Support\Str;

/**
 * Converts incoming request parameter keys to snake_case. Add any keys that
 * should be left untouched to the [except] array.
 */
class ConvertToSnakeCase extends TransformsRequest
{
    /**
     * Attributes that must not be converted.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * Rebuild the nested array, snake-casing each key (unless excepted).
     *
     * @param  array  $data
     * @param  string  $keyPrefix
     * @return array
     */
    protected function cleanArray(array $data, $keyPrefix = '')
    {
        $parameters = [];

        foreach ($data as $key => $value) {
            $fullKey = $keyPrefix.$key;
            $parameters[in_array($fullKey, $this->except) ? $fullKey : Str::snake($fullKey)] = $value;
        }

        return $parameters;
    }
}
