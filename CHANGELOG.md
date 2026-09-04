# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-09-03

### Removed

- **BREAKING**: the `wordpress.` key prefix, which forced a key onto the WordPress side and was stripped before the gettext lookup. It was unused across the framework, the skeleton and the default theme, and broken for its own purpose: gettext extraction tools (`wp i18n make-pot`, Poedit) read the literal `__()` argument, so `__('wordpress.Save changes')` was extracted as the msgid `wordpress.Save changes` — a string the runtime never looks up once the prefix is stripped, leaving the translator's work unreachable. The existing routing already covers every case without mutating the key: pass a text domain for WordPress, a replacement array for Laravel.

## [1.1.0] - 2026-09-03

### Fixed

- **`__($key, [...])` no longer fatals when the key is absent from the current locale.** The guard normalised only the empty array to the `default` text domain, so a non-empty replacement array travelled into WordPress's `translate()` as the domain and reached `isset($l10n[$domain])`, raising `TypeError: Cannot access offset of type array in isset or empty`. It affected every `__($key, [...])` call whose key was not in the locale's catalogue — typically a site whose sources and language are both English, where no `en_US.json` exists at all, taking down every string carrying a placeholder.
- `Lang::has()` ran unguarded on every call, including the default empty-array one, and raised `A facade root has not been set.` wherever `__()` runs before or without an application (mu-plugin, WP-CLI, early bootstrap). The facade root is now read defensively.
- The `wordpress.` prefix was stripped with an unanchored `str_replace()`, so `__('Go to wordpress.org')` returned `Go to org`.
- A Laravel group key resolved to an array, breaking callers typed against WordPress's `string` return. Such lines now defer to WordPress.
- `'0'` was folded into the `default` text domain — a Rector artefact of the original `empty()` check. It is a usable domain name again.
- Laravel's JSON lookup matches the locale exactly and has no language fallback, so a `fr_FR` site never consulted `lang/fr.json`. The base language is now tried after the regional one; an existing `lang/fr_FR.json` still wins.

### Changed

- Routing is now decided by the **caller's intent** rather than by whether Laravel happens to hold the key. A string second argument is a WordPress text domain, a non-empty array is a Laravel call, an empty array is the only ambiguous case and consults both. On the array path a key Laravel does not hold takes its line from the WordPress catalogue and still gets its placeholders filled, so a core or WooCommerce string keeps its translation instead of leaking `:brand` to visitors.
- The `try`/`catch` around `trans()` is gone. Its "fall back to WordPress" branch could never be taken — `$locale` is filled from `get_locale()` just above it — and `trans()` does not throw on a missing key.
- Requires PHP 8.3 (was 8.2).

### Added

- The logic moved out of the global function into `TranslationResolver` behind two gateways, so it can be tested without a container.
- Pest test suite, PHPStan level 9, Pint, Rector, and CI.

## [1.0.1] - 2026-05-12

### Fixed

- Widened the PHP constraint from `^8.2.0` to `^8.2`.

## [1.0.0] - 2025-01-20

Initial release: a single `__()` serving both the Laravel and WordPress translation catalogues.

[1.2.0]: https://github.com/Pollora/Helper-Overrider/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/Pollora/Helper-Overrider/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/Pollora/Helper-Overrider/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/Pollora/Helper-Overrider/releases/tag/1.0.0
