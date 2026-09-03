<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Translation;

/**
 * Read access to the Laravel translation catalogue.
 *
 * Implementations must never throw when the Laravel container is absent:
 * the `__()` helper is autoloaded through `composer.json` `autoload.files`,
 * so it is callable long before (and sometimes entirely without) an
 * application instance. Callers rely on {@see self::isAvailable()} to decide
 * whether the catalogue can be consulted at all.
 */
interface LaravelTranslator
{
    /**
     * Whether a booted application exposing a `translator` binding is reachable.
     */
    public function isAvailable(): bool;

    /**
     * Translate a key held by the Laravel catalogue.
     *
     * Returns `null` when the key is absent, so that the caller can fall back
     * to WordPress. A key missing from Laravel is an expected outcome here,
     * not an error, and implementations must not let it trigger Laravel's
     * missing-translation-key handlers.
     *
     * Also returns `null` when the catalogue holds a non-string value for the
     * key: a group key such as `__('validation')` resolves to an array, which
     * cannot be handed back to callers typed against WordPress's `string`
     * return.
     *
     * @param  array<array-key, mixed>  $replace
     */
    public function translate(string $key, array $replace, ?string $locale): ?string;
}
