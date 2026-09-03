<?php

declare(strict_types=1);

use Pollora\HelperOverrider\Tests\Doubles\WordPressState;

if (! function_exists('translate')) {
    /**
     * Stand-in for WordPress's `translate()`.
     *
     * The `string $domain` type is load-bearing: the bug this package was fixed
     * for was an array reaching this parameter, where WordPress raises
     * "Cannot access offset of type array in isset or empty". Declaring the
     * type turns that same mistake into an immediate TypeError here.
     */
    function translate(string $text, string $domain = 'default'): string
    {
        WordPressState::$translateCalls[] = [$text, $domain];

        return WordPressState::$catalogue[$domain][$text] ?? $text;
    }
}

if (! function_exists('get_locale')) {
    function get_locale(): string
    {
        return WordPressState::$locale ?? '';
    }
}

if (! function_exists('wp_cache_get')) {
    function wp_cache_get(string $key, string $group = '', bool $force = false, mixed &$found = null): mixed
    {
        $found = false;

        return false;
    }
}
