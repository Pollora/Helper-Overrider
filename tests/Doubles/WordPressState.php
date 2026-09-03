<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Tests\Doubles;

/**
 * Mutable state behind the WordPress function doubles in `tests/wordpress-functions.php`.
 *
 * The real functions are global and stateful, so the doubles are too; this class
 * is what lets a test drive them and assert on what they received.
 */
final class WordPressState
{
    /**
     * Gettext catalogues, keyed by text domain then by untranslated string.
     *
     * @var array<string, array<string, string>>
     */
    public static array $catalogue = [];

    /**
     * The locale `get_locale()` reports.
     */
    public static ?string $locale = 'en_US';

    /**
     * Every `translate()` call, as [text, domain] pairs.
     *
     * @var list<array{string, string}>
     */
    public static array $translateCalls = [];

    public static function reset(): void
    {
        self::$catalogue = [];
        self::$locale = 'en_US';
        self::$translateCalls = [];
    }
}
