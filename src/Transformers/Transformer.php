<?php

namespace Omidgfx\Responder\Transformers;

use Closure;
use Illuminate\Contracts\Container\Container;
use LogicException;
use Omidgfx\Responder\Contracts\Transformers\TransformerResolver;
use Omidgfx\Responder\Fractal\TransformerAbstract;
use Omidgfx\Responder\Support\MissingValue;
use Omidgfx\Responder\TransformBuilder;
use Omidgfx\Responder\TransformContext;
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
     * The context this transformation runs in - the values the caller
     * passed down. Transformers never read the request or the
     * environment themselves; everything they need to know about the
     * outside arrives through here.
     *
     * @return TransformContext
     */
    protected function context(): TransformContext
    {
        $scope = $this->getCurrentScope();

        return $scope !== null ? $scope->getContext() : new TransformContext;
    }

    /**
     * Read a value from the context.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    protected function get(string $key, $default = null)
    {
        return $this->context()->get($key, $default);
    }

    /**
     * Whether the context carries the given key.
     *
     * @param  string  $key
     * @return bool
     */
    protected function has(string $key): bool
    {
        return $this->context()->has($key);
    }

    /**
     * A field that only exists while the context value is truthy. The
     * closure receives the value; when the condition fails the field is
     * omitted from the response entirely, unless a fallback is given.
     *
     * @param  string    $key
     * @param  \Closure  $value
     * @param  mixed     $default
     * @return mixed
     */
    protected function when(string $key, Closure $value, $default = null)
    {
        if (func_num_args() < 3) {
            $default = new MissingValue;
        }

        $context = $this->context();

        if (! $context->has($key) || ! $context->get($key)) {
            return $default;
        }

        return $value($context->get($key));
    }

    /**
     * A field that only exists while the context carries the key - the
     * value itself may be falsy. The closure receives the value; when
     * the key is missing the field is omitted from the response
     * entirely, unless a fallback is given.
     *
     * @param  string    $key
     * @param  \Closure  $value
     * @param  mixed     $default
     * @return mixed
     */
    protected function whenHas(string $key, Closure $value, $default = null)
    {
        if (func_num_args() < 3) {
            $default = new MissingValue;
        }

        $context = $this->context();

        if (! $context->has($key)) {
            return $default;
        }

        return $value($context->get($key));
    }

    /**
     * Transform nested data inline, via another transformer: ready data
     * in, transformed array out, null when the data is null. The nested
     * transformation runs in the same context this transformer received,
     * so values passed down by the caller keep flowing at every depth.
     *
     * @param  mixed       $data
     * @param  mixed       $transformer
     * @param  string|null $resourceKey
     * @return array|null
     */
    protected function via($data = null, $transformer = null, ?string $resourceKey = null): ?array
    {
        if ($data === null) {
            return null;
        }

        $builder = $this->resolveContainer()->make(TransformBuilder::class);

        return $builder
            ->context($this->context()->all())
            ->resource($data, $transformer, $resourceKey)
            ->transform()['data'] ?? null;
    }

    /**
     * Transform nested data that is guaranteed by the caller's domain
     * invariant: unlike [via] this never returns null - an assured value
     * that resolves to null fails visibly instead of leaking into the
     * response.
     *
     * @param  mixed       $data
     * @param  mixed       $transformer
     * @param  string|null $resourceKey
     * @return array
     *
     * @throws LogicException
     */
    protected function viaAssured($data, $transformer = null, ?string $resourceKey = null): array
    {
        $transformed = $this->via($data, $transformer, $resourceKey);

        if ($transformed === null) {
            throw new LogicException('An assured nested transform resolved to null.');
        }

        return $transformed;
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
