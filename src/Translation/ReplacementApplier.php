<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Translation;

use BackedEnum;
use Closure;
use Stringable;
use UnitEnum;

/**
 * Substitutes Laravel-style named placeholders into a line.
 *
 * This mirrors the semantics of `Illuminate\Translation\Translator::makeReplacements()`,
 * which is `protected` and therefore unreachable. Reimplementing it is not merely a
 * convenience: on the WordPress-fallback path the line comes from a gettext catalogue
 * rather than the Laravel one, and that path must keep working in a pure WordPress
 * context where no application — and thus no translator — exists at all.
 *
 * Parity with Laravel is pinned by a test that diffs this class against a real
 * `Illuminate\Translation\Translator` over a matrix of replacement shapes.
 *
 * One documented divergence: per-class handlers registered through
 * `Lang::stringable()` are instance state on the translator and cannot be read from
 * here, so such objects fall back to their `__toString()` representation.
 */
final class ReplacementApplier
{
    /**
     * @param  array<array-key, mixed>  $replace
     */
    public function apply(string $line, array $replace): string
    {
        if ($replace === []) {
            return $line;
        }

        $shouldReplace = [];

        foreach ($replace as $key => $value) {
            $key = (string) $key;

            // Closures wrap a delimited span rather than substituting a token:
            // ':name' has no meaning here, '<name>…</name>' does.
            if ($value instanceof Closure) {
                $line = (string) preg_replace_callback(
                    '/<'.preg_quote($key, '/').'>(.*?)<\/'.preg_quote($key, '/').'>/',
                    fn (array $matches): string => (string) $value($matches[1]),
                    $line
                );

                continue;
            }

            $value = $this->scalarize($value);

            // Ordering matters: strtr() applies the longest match first, but
            // ':Name' and ':NAME' must be registered alongside ':name' so that
            // a capitalised placeholder picks up a capitalised value.
            $shouldReplace[':'.$this->ucfirst($key)] = $this->ucfirst($value);
            $shouldReplace[':'.$this->upper($key)] = $this->upper($value);
            $shouldReplace[':'.$key] = $value;
        }

        return strtr($line, $shouldReplace);
    }

    /**
     * Equivalent of Str::upper(), inlined to keep the class usable with no Laravel present.
     */
    private function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Equivalent of Str::ucfirst(), inlined to keep the class usable with no Laravel present.
     */
    private function ucfirst(string $value): string
    {
        return $this->upper(mb_substr($value, 0, 1, 'UTF-8')).mb_substr($value, 1, null, 'UTF-8');
    }

    /**
     * Reduce a replacement value to the string Laravel would substitute.
     */
    private function scalarize(mixed $value): string
    {
        $value = match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            default => $value,
        };

        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '',
            is_scalar($value) => (string) $value,
            $value instanceof Stringable, is_object($value) && method_exists($value, '__toString') => (string) $value,
            default => '',
        };
    }
}
