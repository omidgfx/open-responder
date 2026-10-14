# omidgfx/open-responder

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
- **Transform context** — pass the viewer, a locale, any flag down to every
  transformer of the response; `when()` / `whenHas()` turn them into fields,
  `via()` / `viaAssured()` transform nested data inline. Business logic stays
  outside the transformers.
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
return $this->error(123, 'Something broke.')->respond(500);
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

## Passing context into transformers

Transformers turn ready data into responses. They never read the request,
the session or the environment themselves — when the output depends on the
situation (who is watching, which locale, which flags are on), the caller
passes that situation down and the transformer reads it:

```php
return $this
    ->success($activity, ActivityDetailTransformer::class)
    ->context('viewer', $request->playerProfile());
```

`context()` accepts a key/value pair or an array of them and chains freely.
The values travel down every nested transformer of the response, includes and
`via()` calls alike, so each depth reads the same situation:

```php
class ActivityDetailTransformer extends Transformer
{
    public function transform(Activity $activity): array
    {
        return [
            'uuid' => $activity->uuid,

            // a plain value with a default
            'locale' => $this->get('locale', 'en'),

            // a field that only exists while the viewer was passed and is truthy
            'viewer_name' => $this->when('viewer', fn ($viewer) => $viewer->name),

            // presence-based: the value itself may be falsy
            'is_debug' => $this->whenHas('debug', fn ($on) => $on),
        ];
    }
}
```

- `$this->get($key, $default = null)` reads a value.
- `$this->has($key)` tells whether the key was passed.
- `$this->when($key, $fn, $default)` includes `$fn($value)` only when the value
  is truthy — otherwise the field is omitted from the response entirely, unless
  a fallback is given.
- `$this->whenHas($key, $fn, $default)` includes `$fn($value)` whenever the key
  exists, falsy or not — omitted when the key is missing.

Transformers built directly on the bundled Fractal base can read the same bag
through `$this->getCurrentScope()->getContext()`.

### Nested transformations: `via()` and `viaAssured()`

Inside a transformer, nested data transforms through other transformers
inline, via `via()`:

```php
'player' => $this->via($participant->playerProfile, PlayerProfileMicroTransformer::class),
```

`via()` returns `null` for `null` data. `viaAssured()` is the variant for
values the caller's domain guarantees — an assured value that resolves to
`null` fails loudly instead of leaking into the response:

```php
'player' => $this->viaAssured($participant->playerProfile, PlayerProfileMicroTransformer::class),
```

Both run in the transformer's own context, so passed-down values keep flowing
at every depth.

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

## Testing

The repository carries a PHPUnit suite covering the transform context, the
reader methods and the nested transformations:

```bash
composer install
composer test
```

Production installs stay lean: PHPUnit lives in `require-dev`, so an app
consuming the package pulls in only `illuminate/contracts` and
`illuminate/support` — the bundled engine keeps the dependency list at zero
third-party packages.

## License

MIT.
