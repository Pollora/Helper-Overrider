<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Translation;

/**
 * Read access to the WordPress gettext catalogues.
 *
 * Implementations must never throw when WordPress is not loaded: the helper
 * is equally reachable from a plain Laravel console context where no
 * WordPress function exists.
 */
interface WordPressTranslator
{
    /**
     * Whether WordPress's gettext layer is loaded and usable.
     */
    public function isAvailable(): bool;

    /**
     * The active WordPress locale, e.g. `fr_FR`, or `null` when undeterminable.
     */
    public function locale(): ?string;

    /**
     * Translate a string through a WordPress text domain.
     *
     * Returns the untranslated text when the domain holds no entry for it,
     * which is WordPress's own contract for `translate()`.
     */
    public function translate(string $text, string $domain): string;
}
