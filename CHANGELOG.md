# Changelog

All notable changes to this project will be documented in this file. Dates
follow a variant of [Keep a Changelog](https://keepachangelog.com/).

## [1.1.0] - 2026-10-14

### Added
- Transform context: `context($key, $value)` on the transform builder and the
  success response builder passes the situation a transformation runs in —
  the viewer, a locale, a flag — down to every transformer of the resource
  tree. Transformers never read the request or the environment themselves.
- Transformer-side readers on the base transformer: `get($key, $default)`,
  `has($key)`, `when($key, $fn, $default)` and `whenHas($key, $fn, $default)`.
  `when` acts on truthiness, `whenHas` on presence; both omit their field from
  the response entirely when the condition fails, unless a fallback is given.
- Nested transformations on the base transformer: `via($data, $transformer,
  $resourceKey)` transforms nested data inline (null data in, null out) and
  `viaAssured(...)` fails loudly when a guaranteed value resolves to null.
  Both inherit the transformer's context, so passed-down values flow at every
  depth.
- PHPUnit test suite covering the context bag, scope inheritance, the reader
  methods and the nested transformations.

## [1.0.1] - 2026-09-19

### Fixed
- Disabled relation/fieldset query parameters (`null`) no longer fall back to
  `input(null)`, which read the *whole* request bag as relations and sparse
  fieldsets — turning keys like `_domain_role` into phantom includes and, for
  keyless null resources, throwing `LogicException` on every `success(null)`
  response.
- Sparse fieldsets are skipped for `NullResource`: a null payload has no
  fields to filter, so it serializes as `data: null` instead of failing.

## [1.0.0] - 2026-08-30

Initial release.

### Added
- Clean-room, drop-in replacement for `flugg/laravel-responder`.
- Equipped with `Omidgfx\Responder` namespace.
- Fully self-contained transformation engine (Fractal) bundled in
  `src/Fractal` — no external package required.
- Laravel-only service provider (Lumen support removed).
- Behaviour identical to the original; verified against a namespace-swapped
  copy of the original package's own test suite.
- PHP 7.4 base with PHP 8.5 readiness.

### Removed
- `league/fractal` dependency.
- Lumen response factory and Lumen branches.
