<?php

namespace Omidgfx\Responder\Transformers;

use Closure;
use Illuminate\Contracts\Container\Container;
use Omidgfx\Responder\Contracts\Transformers\TransformerResolver;
use Omidgfx\Responder\Fractal\TransformerAbstract;
use Omidgfx\Responder\Transformers\Concerns\HasRelationships;
use Omidgfx\Responder\Transformers\Concerns\MakesResources;
use Omidgfx\Responder\Transformers\Concerns\OverridesFractal;

/**
 * The base transformer class. It merges the relationship, resource-building and
 * Fractal-override behaviours and provides a lazily resolved container so the
 * transformer can resolve nested transformers and resources out of the app
 * container without a hard dependency on it.
 */
abstract class Transformer extends TransformerAbstract
{
    use HasRelationships;
    use MakesResources;
    use OverridesFractal;

    /** @var Closure|null */
    protected static $containerResolver;

    /**
     * Register the callback used to obtain the application container.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function containerResolver(Closure $resolver)
    {
        static::$containerResolver = $resolver;
    }

    /**
     * Resolve and return the application container.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    protected function resolveContainer(): Container
    {
        return call_user_func(static::$containerResolver);
    }

    /**
     * Resolve a transformer by its class name string.
     *
     * @param  string  $transformer
     * @return mixed
     */
    protected function resolveTransformer(string $transformer)
    {
        $transformerResolver = $this->resolveContainer()->make(TransformerResolver::class);

        return $transformerResolver->resolve($transformer);
    }
}
