<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Tests\Doubles;

use Pollora\HelperOverrider\Translation\LaravelTranslator;

/**
 * An in-memory Laravel catalogue, so resolver tests can state exactly which
 * locale holds which key without booting a container.
 */
final class FakeLaravelTranslator implements LaravelTranslator
{
    /**
     * Every lookup, as [key, locale] pairs, in order.
     *
     * @var list<array{string, string|null}>
     */
    public array $lookups = [];

    /**
     * @param  array<string, array<string, string>>  $catalogue  Lines keyed by locale then key.
     */
    public function __construct(
        private readonly array $catalogue = [],
        private readonly bool $available = true,
    ) {}

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function translate(string $key, array $replace, ?string $locale): ?string
    {
        $this->lookups[] = [$key, $locale];

        $line = $this->catalogue[$locale ?? ''][$key] ?? null;

        if ($line === null) {
            return null;
        }

        // Enough of Laravel's substitution to prove the resolver hands
        // replacements down; full fidelity is ReplacementApplier's own concern.
        foreach ($replace as $token => $value) {
            $line = str_replace(':'.$token, is_scalar($value) ? (string) $value : '', $line);
        }

        return $line;
    }
}
