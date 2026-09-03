# Pollora Helper Overrider

Replaces WordPress's `__()` with one that serves **both** the Laravel and the
WordPress translation catalogues, so a Pollora application can write either
style and get the right answer.

## How the override happens

WordPress declares `__()` unguarded in `wp-includes/l10n.php`, so it cannot
simply be redefined. The Pollora framework patches WordPress core to rename it
to `__wp()` (`patches/wordpress-core.patch`, applied through
`cweagans/composer-patches`), which frees the name for this package's
`src/helpers.php`, loaded via Composer's `autoload.files`.

> **This package must stay dependency-free.**
>
> `laravel/framework` also declares `__()`, behind the same `function_exists()`
> guard, and Composer emits `autoload.files` in dependency order. The package
> only wins the race while nothing sorts it after `laravel/framework`. Adding an
> `illuminate/*` entry to `require` fails nothing loudly — `__()` silently
> becomes Laravel's, and every WordPress catalogue stops resolving. The Laravel
> classes are reached through PSR-4 at call time instead, long after the
> autoloader is up, and guarded by `class_exists()`. `tests/Unit/OverrideOrderTest.php`
> is the alarm for this.

## How a call is routed

The two translators do not overlap, and the **second argument** is what tells
them apart.

| Call | Routed to | Why |
|---|---|---|
| `__('Some string')` | Laravel, then WordPress `default` | No intent expressed. The only ambiguous case, and the only one where both are consulted. |
| `__('Some string', 'my-plugin')` | WordPress, `my-plugin` domain | A text domain is an explicit WordPress call. Laravel is never consulted, so an unrelated key of the same name cannot shadow a plugin's catalogue. |
| `__('Shipping :brand', ['brand' => 'X'])` | Laravel, then WordPress `default` — placeholders filled either way | Named replacements are a Laravel idiom. WordPress has no named placeholders: a `msgid` uses `%s`, substituted by `sprintf()` at the call site. |

There is no key-prefix escape hatch to force one side over the other. A prefix
like `wordpress.` would be invisible to gettext extraction tools (`wp i18n
make-pot`, Poedit) — they read the literal `__()` argument, so a translator
would translate a `msgid` the runtime never actually looks up once the prefix
is stripped. If you need WordPress specifically, pass its text domain; if you
need Laravel specifically, pass replacements (even an unused empty one won't
do — see the ambiguous row above).

For the third row, if the key is absent from the Laravel catalogue the line is
taken from WordPress — which returns the key verbatim when it knows nothing
about it — and the named placeholders are filled in regardless. That is what
keeps `__('Shipping :brand', ['brand' => 'Test'])` from rendering as
`Shipping :brand` in a locale you have not translated.

### Locale resolution

The WordPress locale (`fr_FR`) is tried first, then its base language (`fr`).
Laravel's JSON lookup — the one `__()` uses — matches the locale exactly and has
no language fallback of its own, so without this a project shipping
`lang/fr.json` would never be consulted on a `fr_FR` site. An existing
`lang/fr_FR.json` still wins.

## Scope

Only `__()` is overridden. The rest of the WordPress family (`_e()`, `_x()`,
`_n()`, `esc_html__()` …) does **not** route through `__()` in core — those call
`translate()`, `translate_with_gettext_context()` and `translate_plural()`
directly — so each would need its own core patch. Two of them have no faithful
mapping anyway:

- `_x()` / `_ex()` — Laravel has no notion of a gettext context. Ignoring it
  would collapse two strings distinguished only by context onto one Laravel key.
- `_n()` / `_nx()` — WordPress takes `(singular, plural, count)` and delegates
  plural forms to the `.mo` `Plural-Forms` header; Laravel encodes pluralisation
  inside the string and resolves it with `trans_choice()`. Routing `_n()` to
  Laravel would make its `$plural` argument dead and break languages with more
  than two forms.

## Known limitation

Per-class handlers registered through `Lang::stringable()` apply on the Laravel
path but not on the WordPress-fallback path, where such objects fall back to
`__toString()`. The fallback line comes from a gettext catalogue rather than the
Laravel one and must keep working with no application booted, so it substitutes
placeholders through `ReplacementApplier` rather than through the translator.
`ReplacementApplierParityTest` diffs that class against a real
`Illuminate\Translation\Translator` to keep the two in step.

## Requirements

- PHP 8.3+
- Laravel 12 or 13, at runtime (not as a Composer dependency — see above)

## Development

```bash
composer test          # pint + rector + phpstan + pest
composer test:unit     # pest only
composer lint          # apply Pint
composer refacto       # apply Rector
```

## License

GPL-2.0-or-later
