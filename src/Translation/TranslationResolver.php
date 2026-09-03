<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Translation;

/**
 * Routes a `__()` call to Laravel or to WordPress.
 *
 * The two translators do not overlap, and the second argument is what tells
 * them apart:
 *
 * - A **string** is a WordPress text domain. WordPress owns the call.
 * - A **non-empty array** is a set of Laravel named replacements. WordPress has
 *   no named placeholders — a `msgid` uses `%s`, substituted by `sprintf()` at
 *   the call site — so nothing but Laravel can serve it.
 * - An **empty array** is the default and carries no intent either way. This is
 *   the only genuinely ambiguous case, and the only one where both catalogues
 *   are consulted.
 *
 * A key can be forced onto the WordPress side by prefixing it with
 * `wordpress.`, which is stripped before the gettext lookup.
 */
final readonly class TranslationResolver
{
    /**
     * Prefix that opts a key out of the Laravel catalogue.
     */
    private const string WORDPRESS_PREFIX = 'wordpress.';

    /**
     * WordPress's fallback text domain, used whenever the caller names none.
     */
    private const string DEFAULT_DOMAIN = 'default';

    public function __construct(
        private LaravelTranslator $laravel,
        private WordPressTranslator $wordpress,
        private ReplacementApplier $replacements = new ReplacementApplier,
    ) {}

    /**
     * @param  array<array-key, mixed>|string  $replace  Laravel replacements, or a WordPress text domain.
     * @param  string|null  $locale  Laravel locale; ignored by the WordPress side.
     */
    public function translate(string $key, array|string $replace = [], ?string $locale = null): string
    {
        // An explicit text domain is an explicit WordPress call. Consulting
        // Laravel here would let an unrelated key of the same name shadow a
        // plugin's own catalogue.
        if (is_string($replace)) {
            return $this->fromWordPress($key, $replace === '' ? self::DEFAULT_DOMAIN : $replace);
        }

        $line = $this->fromLaravel($key, $replace, $locale);

        if ($line !== null) {
            return $line;
        }

        $line = $this->fromWordPress($key, self::DEFAULT_DOMAIN);

        // Named replacements survive the fallback: WordPress returned the line
        // (translated, or the key verbatim when it knows nothing about it), and
        // the placeholders still have to be filled in. Without this, a key
        // absent from the current locale's Laravel catalogue would render as
        // "Shipping :brand" to the visitor.
        return $this->replacements->apply($line, $replace);
    }

    /**
     * Look the key up in the Laravel catalogue, or return null to defer to WordPress.
     *
     * @param  array<array-key, mixed>  $replace
     */
    private function fromLaravel(string $key, array $replace, ?string $locale): ?string
    {
        if (str_starts_with($key, self::WORDPRESS_PREFIX) || ! $this->laravel->isAvailable()) {
            return null;
        }

        foreach ($this->localeCandidates($locale) as $candidate) {
            $line = $this->laravel->translate($key, $replace, $candidate);

            if ($line !== null) {
                return $line;
            }
        }

        return null;
    }

    /**
     * Look the key up in a WordPress text domain.
     *
     * Falls back to the key itself outside a WordPress context, which matches
     * what `translate()` returns for an unknown string.
     */
    private function fromWordPress(string $key, string $domain): string
    {
        // Anchored to the start: a plain str_replace() would also rewrite
        // "Go to wordpress.org" into "Go to org".
        if (str_starts_with($key, self::WORDPRESS_PREFIX)) {
            $key = substr($key, strlen(self::WORDPRESS_PREFIX));
        }

        return $this->wordpress->translate($key, $domain);
    }

    /**
     * Locales to try, most specific first.
     *
     * WordPress reports `fr_FR` where Laravel projects conventionally ship
     * `lang/fr.json`, and Laravel's JSON lookup — the one `__()` uses — matches
     * the locale exactly, with no language fallback of its own. Appending the
     * base language is purely additive: an existing `lang/fr_FR.json` still wins.
     *
     * @return list<string|null>
     */
    private function localeCandidates(?string $locale): array
    {
        $locale ??= $this->wordpress->locale();

        if ($locale === null || $locale === '') {
            // Let Laravel fall back to its own configured locale.
            return [null];
        }

        $base = explode('_', str_replace('-', '_', $locale))[0];

        if ($base === '' || $base === $locale) {
            return [$locale];
        }

        return [$locale, $base];
    }
}
