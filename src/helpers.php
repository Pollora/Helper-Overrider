<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

if (! function_exists('__')) {
    /**
     * Tries to get a translation from both Laravel and WordPress.
     *
     * @param  string  $key  key of the translation
     * @param  array|string  $replace  replacements for laravel or domain for wordpress
     * @param  string|null  $locale  locale for laravel, not used for wordpress
     * @return string
     */
    function __(string $key, array|string $replace = [], ?string $locale = null)
    {
        if (($locale === null || $locale === '' || $locale === '0') && function_exists('get_locale') && function_exists('wp_cache_get')) {
            $locale = get_locale();
        }
        if (is_array($replace) && Lang::has($key, $locale)) {
            try {
                return trans($key, $replace, $locale);
            } catch (\Exception $e) {
                // failed to get translation from Laravel
                if (($replace !== []) || ! empty($locale)) {
                    // this doesn't look like something we can pass to WordPress, lets
                    // rethrow the exception
                    throw $e;
                }
            }
        }

        $key = str_replace('wordpress.', '', $key);

        return function_exists('translate') ? translate($key, $replace === '' || $replace === '0' || $replace === [] ? 'default' : $replace) : $key;
    }
}