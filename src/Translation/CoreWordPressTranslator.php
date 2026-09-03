<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Translation;

/**
 * Reaches the WordPress gettext catalogues through core functions.
 */
final class CoreWordPressTranslator implements WordPressTranslator
{
    public function isAvailable(): bool
    {
        return function_exists('translate');
    }

    public function locale(): ?string
    {
        // wp_cache_get() is checked alongside get_locale() because the latter
        // reads options, which needs the object cache to be up. Calling it too
        // early during bootstrap yields a wrong or fatal result.
        if (! function_exists('get_locale') || ! function_exists('wp_cache_get')) {
            return null;
        }

        $locale = get_locale();

        return $locale === '' ? null : $locale;
    }

    public function translate(string $text, string $domain): string
    {
        if (! $this->isAvailable()) {
            return $text;
        }

        return translate($text, $domain);
    }
}
