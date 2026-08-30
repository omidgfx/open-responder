<?php

namespace Omidgfx\Responder;

use Omidgfx\Responder\Console\MakeTransformer;
use Omidgfx\Responder\Contracts\ErrorFactory as ErrorFactoryContract;
use Omidgfx\Responder\Contracts\ErrorMessageResolver as ErrorMessageResolverContract;
use Omidgfx\Responder\Contracts\ErrorSerializer as ErrorSerializerContract;
use Omidgfx\Responder\Contracts\Pagination\PaginatorFactory as PaginatorFactoryContract;
use Omidgfx\Responder\Contracts\Resources\ResourceFactory as ResourceFactoryContract;
use Omidgfx\Responder\Contracts\Resources\ResourceKeyResolver as ResourceKeyResolverContract;
use Omidgfx\Responder\Contracts\Responder as ResponderContract;
use Omidgfx\Responder\Contracts\ResponseFactory as ResponseFactoryContract;
use Omidgfx\Responder\Contracts\Transformers\TransformerResolver as TransformerResolverContract;
use Omidgfx\Responder\Contracts\TransformFactory as TransformFactoryContract;
use Omidgfx\Responder\Fractal\Manager;
use Omidgfx\Responder\Fractal\Serializer\SerializerAbstract;
use Omidgfx\Responder\Http\Responses\ErrorResponseBuilder;
use Omidgfx\Responder\Http\Responses\Factories\LaravelResponseFactory;
use Omidgfx\Responder\Pagination\PaginatorFactory;
use Omidgfx\Responder\Resources\ResourceFactory;
use Omidgfx\Responder\Resources\ResourceKeyResolver;
use Omidgfx\Responder\Transformers\Transformer as BaseTransformer;
use Omidgfx\Responder\Transformers\TransformerResolver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Translation\Translator;

/**
 * Registers all of the package's services with the Laravel container.
 *
 * One instance of the response factory is shared per request so decorators are
 * applied once; everything else is resolved on demand.
 */
class ResponderServiceProvider extends BaseServiceProvider
{
    /**
     * Whether the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLaravelBindings();
        $this->registerSerializerBindings();
        $this->registerErrorBindings();
        $this->registerFractalBindings();
        $this->registerTransformerBindings();
        $this->registerResourceBindings();
        $this->registerPaginationBindings();
        $this->registerTransformationBindings();
        $this->registerServiceBindings();
    }

    /**
     * Wire the response factory so that configured decorators wrap the base one.
     *
     * @return void
     */
    protected function registerLaravelBindings()
    {
        $this->app->singleton(ResponseFactoryContract::class, function ($app) {
            $factory = $this->app->make(LaravelResponseFactory::class);

            foreach ($this->app->config['responder.decorators'] as $decorator) {
                $factory = new $decorator($factory);
            }

            return $factory;
        });

        $this->app->bind(Translator::class, function ($app) {
            return $app['translator'];
        });
    }

    /**
     * Bind the success and error serializer implementations.
     *
     * @return void
     */
    protected function registerSerializerBindings()
    {
        $this->app->bind(ErrorSerializerContract::class, function ($app) {
            return $app->make($app->config['responder.serializers.error']);
        });

        $this->app->bind(SerializerAbstract::class, function ($app) {
            return $app->make($app->config['responder.serializers.success']);
        });
    }

    /**
     * Bind the error factory, message resolver, and error response builder.
     *
     * @return void
     */
    protected function registerErrorBindings()
    {
        $this->app->singleton(ErrorMessageResolverContract::class, function ($app) {
            return $app->make(ErrorMessageResolver::class);
        });

        $this->app->singleton(ErrorFactoryContract::class, function ($app) {
            return $app->make(ErrorFactory::class);
        });

        $this->app->bind(ErrorResponseBuilder::class, function ($app) {
            $builder = (new ErrorResponseBuilder(
                $app->make(ResponseFactoryContract::class),
                $app->make(ErrorFactoryContract::class)
            ))->serializer($app->make(ErrorSerializerContract::class));

            return $builder;
        });
    }

    /**
     * Configure the Fractal manager (recursion limit only).
     *
     * @return void
     */
    protected function registerFractalBindings()
    {
        $this->app->bind(Manager::class, function ($app) {
            return (new Manager)->setRecursionLimit($app->config['responder.recursion_limit']);
        });
    }

    /**
     * Bind the transformer resolver and its container-aware fallback.
     *
     * @return void
     */
    protected function registerTransformerBindings()
    {
        $this->app->singleton(TransformerResolverContract::class, function ($app) {
            return new TransformerResolver($app, $app->config['responder.fallback_transformer']);
        });

        BaseTransformer::containerResolver(function () {
            return $this->app->make(Container::class);
        });
    }

    /**
     * Bind the resource factory and resource key resolver.
     *
     * @return void
     */
    protected function registerResourceBindings()
    {
        $this->app->singleton(ResourceKeyResolverContract::class, function ($app) {
            return $app->make(ResourceKeyResolver::class);
        });

        $this->app->singleton(ResourceFactoryContract::class, function ($app) {
            return $app->make(ResourceFactory::class);
        });
    }

    /**
     * Bind the paginator factory using the current request's query params.
     *
     * @return void
     */
    protected function registerPaginationBindings()
    {
        $this->app->bind(PaginatorFactoryContract::class, function ($app) {
            return new PaginatorFactory($app->make(Request::class)->query());
        });
    }

    /**
     * Bind the transform factory and the transform builder, preloaded with the
     * relations and fieldsets requested through the query string.
     *
     * @return void
     */
    protected function registerTransformationBindings()
    {
        $this->app->bind(TransformFactoryContract::class, function ($app) {
            return $app->make(FractalTransformFactory::class);
        });

        $this->app->bind(TransformBuilder::class, function ($app) {
            $request = $this->app->make(Request::class);
            $relations = $request->input($this->app->config['responder.load_relations_parameter'], []);
            $fieldsets = $request->input($app->config['responder.filter_fields_parameter'], []);

            return (new TransformBuilder(
                $app->make(ResourceFactoryContract::class),
                $app->make(TransformFactoryContract::class),
                $app->make(PaginatorFactoryContract::class)
            ))->serializer($app->make(SerializerAbstract::class))
                ->with(is_string($relations) ? explode(',', $relations) : $relations)
                ->only($fieldsets);
        });
    }

    /**
     * Bind the main responder service.
     *
     * @return void
     */
    protected function registerServiceBindings()
    {
        $this->app->bind(ResponderContract::class, function ($app) {
            return $app->make(Responder::class);
        });
    }

    /**
     * Bootstrap the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/responder.php', 'responder');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/responder.php' => base_path('config/responder.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../resources/lang/en/errors.php' => base_path('resources/lang/en/errors.php'),
            ], 'lang');
        }

        $this->commands(MakeTransformer::class);
    }
}
