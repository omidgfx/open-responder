# Changelog

All notable changes to this project will be documented in this file. Dates
follow a variant of [Keep a Changelog](https://keepachangelog.com/).

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
