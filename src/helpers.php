<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

if (! function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = []): bool
    {
        $result = app('wp.mail')->send($to, $subject, $message, $headers, $attachments);

        return $result !== null;
    }
}

if (! function_exists('mysqli_report')) {
    /**
     * Report MySQL errors.
     */
    function mysqli_report(): void
    {
        // silence is golden
    }
}

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
        if (($locale === null || $locale === '' || $locale === '0') && function_exists('get_locale')) {
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

        return translate($key, $replace === '' || $replace === '0' || $replace === [] ? 'default' : $replace);
    }
}

if (! function_exists('is_secured')) {
    function is_secured(): bool
    {
        return str_contains((string) config('app.url'), 'https://');
    }
}
