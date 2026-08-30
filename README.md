# omidgfx/laravel-responder

A self-contained Laravel **API response builder** with transformer support.

It behaves exactly like [`flugg/laravel-responder`](https://github.com/flugg/laravel-responder)
(the Fractal-backed response / transformer package) but is a clean-room, drop-in
replacement:

- **No external package dependencies** — the transformation engine (the part
  normally supplied by `league/fractal`) is bundled inside this package. The
  only `require` entries are Laravel's own `illuminate/contracts` and
  `illuminate/support`.
- **Laravel only** — Lumen support was removed, so the service provider is
  lean and there is no extra factory / branch to maintain.
- **Drop-in compatible** — swap your `use` statements and nothing changes.
- **PHP 7.4 base, PHP 8.5 ready** — source uses only PHP 7.4+ syntax and
  avoids 8.4+ deprecations such as implicit nullable parameter types.

## Why this package exists

You use `flugg/laravel-responder` for the *behaviour*, not for Fractal itself.
This package reproduces that behaviour exactly but removes the indirection you
never asked for. You only need to change one line per import:

```php
// before
use Flugg\Responder\Responder;

// after
use Omidgfx\Responder\Responder;
```

and keep your transformers, controllers and responses exactly as they were.

## Installation

```bash
composer require omidgfx/laravel-responder
```

The package auto-registers its service provider and the `Responder` /
`Transformer` facades through Composer's `extra.laravel` config.

## Quick start

Building a success response in a controller (using the `MakesResponses` trait):

```php
use Omidgfx\Responder\Http\MakesResponses;

class BookController
{
    use MakesResponses;

    public function index()
    {
        return $this->success(Book::all(), BookTransformer::class);
    }
}
```

The equivalent via the facade:

```php
use Omidgfx\Responder\Facades\Responder;

return Responder::success(Book::all(), BookTransformer::class)->respond(200);
```

Building an error response:

```php
return $this->error('page_not_found')->respond(404);
// or
return this->error(123, 'Something broke.')->respond(500);
```

Defining a transformer:

```php
use Omidgfx\Responder\Transformers\Transformer;

class BookTransformer extends Transformer
{
    protected $relations = [
        'author' => AuthorTransformer::class,
    ];

    protected $load = [
        'chapters',
    ];

    public function transform(Book $book)
    {
        return [
            'id' => (int) $book->id,
            'title' => $book->title,
        ];
    }
}
```

## Configuration

Publish the config file to customise the serializers, decorators, fallback
transformer, query-string relation/field parameters, recursion limit and
camel-casing:

```bash
php artisan vendor:publish --provider="Omidgfx\Responder\ResponderServiceProvider" --tag=config
```

## The bundled engine

`src/Fractal/` contains the transformation engine (Manager, Scope, resources,
serializers and pagination adapters) that normally ships as `league/fractal`.
It is bundled under the `Omidgfx\Responder\Fractal` namespace so the package
runs with **zero required third-party package**.

## No dev dependencies

The package ships **no tests, no PHPUnit and no Mockery** — `composer install`
pulls in only the production `illuminate/contracts` and `illuminate/support`
packages. There is no `tests/` directory and nothing extra to run.

## License

MIT.
