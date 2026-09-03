<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Translation;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\Translator;

/**
 * Reaches the Laravel catalogue through the facade application.
 *
 * The facade root is read defensively rather than through `Lang::` static
 * calls: `Lang::has()` on an unbooted application raises
 * `A facade root has not been set.`, and this helper runs on paths where no
 * application exists at all.
 */
final class FacadeLaravelTranslator implements LaravelTranslator
{
    public function isAvailable(): bool
    {
        return $this->translator() instanceof TranslatorContract;
    }

    public function translate(string $key, array $replace, ?string $locale): ?string
    {
        $translator = $this->translator();

        if (! $translator instanceof TranslatorContract) {
            return null;
        }

        if (! $this->holds($translator, $key, $locale)) {
            return null;
        }

        $line = $translator->get($key, $replace, $locale);

        // A group key resolves to an array. WordPress callers are typed against
        // a string return, so treat it as an unusable line and let the caller
        // fall through to WordPress.
        return is_string($line) ? $line : null;
    }

    /**
     * Whether the catalogue holds the key, without reporting it as missing.
     *
     * A key we are about to hand over to WordPress is not a missing Laravel
     * translation, so the missing-key handlers must not see it. `has()` is what
     * suppresses them, but it lives on the concrete translator rather than on
     * the contract, so a custom contract-only implementation is probed instead:
     * `get()` returning the key verbatim is Laravel's own signal for a miss.
     */
    private function holds(TranslatorContract $translator, string $key, ?string $locale): bool
    {
        if ($translator instanceof Translator) {
            return $translator->has($key, $locale);
        }

        $probe = $translator->get($key, [], $locale);

        return $probe !== $key;
    }

    /**
     * The bound translator, or null when no application is reachable.
     */
    private function translator(): ?TranslatorContract
    {
        // The package carries no Composer dependency on Laravel — that is what
        // keeps its helpers.php ahead of laravel/framework's in autoload.files —
        // so Laravel may legitimately be absent altogether.
        if (! class_exists(Facade::class)) {
            return null;
        }

        $app = Facade::getFacadeApplication();

        if ($app === null || ! $app->bound('translator')) {
            return null;
        }

        $translator = $app->make('translator');

        return $translator instanceof TranslatorContract ? $translator : null;
    }
}
