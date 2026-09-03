<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Tests\Doubles;

use Pollora\HelperOverrider\Translation\WordPressTranslator;

/**
 * An in-memory gettext catalogue for resolver tests.
 */
final class FakeWordPressTranslator implements WordPressTranslator
{
    /**
     * Every lookup, as [text, domain] pairs, in order.
     *
     * @var list<array{string, string}>
     */
    public array $lookups = [];

    /**
     * @param  array<string, array<string, string>>  $catalogue  Translations keyed by domain then text.
     */
    public function __construct(
        private readonly array $catalogue = [],
        private readonly ?string $locale = 'en_US',
        private readonly bool $available = true,
    ) {}

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function locale(): ?string
    {
        return $this->locale;
    }

    public function translate(string $text, string $domain): string
    {
        $this->lookups[] = [$text, $domain];

        if (! $this->available) {
            return $text;
        }

        return $this->catalogue[$domain][$text] ?? $text;
    }
}
