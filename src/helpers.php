<?php

declare(strict_types=1);

use Pollora\HelperOverrider\Translation\CoreWordPressTranslator;
use Pollora\HelperOverrider\Translation\FacadeLaravelTranslator;
use Pollora\HelperOverrider\Translation\TranslationResolver;

if (! function_exists('pollora_translation_resolver')) {
    /**
     * The shared resolver backing `__()`.
     *
     * Built lazily and kept for the request: this file is loaded through
     * Composer's `autoload.files`, long before any application exists, so the
     * resolver cannot be wired at load time. The instance holds no state of its
     * own — it reads the container and the WordPress globals on every call.
     */
    function pollora_translation_resolver(): TranslationResolver
    {
        static $resolver = null;

        return $resolver ??= new TranslationResolver(
            new FacadeLaravelTranslator,
            new CoreWordPressTranslator,
        );
    }
}

if (! function_exists('__')) {
    /**
     * Translate a string through Laravel or WordPress.
     *
     * Replaces WordPress's own `__()`, which the framework renames to `__wp()`
     * via a patch on `wp-includes/l10n.php`. The signature is a superset of
     * both originals, so calls written for either side keep working:
     *
     *     __('Some string')                        // WordPress 'default' domain, or Laravel
     *     __('Some string', 'my-plugin')           // WordPress, explicit text domain
     *     __('Shipping :brand', ['brand' => 'Ex']) // Laravel named replacements
     *
     * @param  string  $key  Translation key, or untranslated text.
     * @param  array<array-key, mixed>|string  $replace  Laravel replacements, or a WordPress text domain.
     * @param  string|null  $locale  Laravel locale; ignored by the WordPress side.
     */
    function __(string $key, array|string $replace = [], ?string $locale = null): string
    {
        return pollora_translation_resolver()->translate($key, $replace, $locale);
    }
}
