<?php

declare(strict_types=1);

use Pollora\HelperOverrider\Translation\FacadeLaravelTranslator;

function gateway(): FacadeLaravelTranslator
{
    return new FacadeLaravelTranslator;
}

describe('availability', function (): void {
    // src/helpers.php is autoloaded through Composer's autoload.files, so __()
    // is callable before any application exists. Reading the facade root through
    // Lang:: would raise "A facade root has not been set." on those paths.
    it('reports unavailable with no facade application, instead of throwing', function (): void {
        expect(gateway()->isAvailable())->toBeFalse()
            ->and(gateway()->translate('Save', [], 'en'))->toBeNull();
    });

    it('reports unavailable when the application has no translator binding', function (): void {
        bootApplication();

        expect(gateway()->isAvailable())->toBeFalse()
            ->and(gateway()->translate('Save', [], 'en'))->toBeNull();
    });

    it('reports unavailable when the translator binding is not a translator', function (): void {
        bootApplication()->instance('translator', new stdClass);

        expect(gateway()->isAvailable())->toBeFalse();
    });

    it('reports available once a translator is bound', function (): void {
        bootTranslator();

        expect(gateway()->isAvailable())->toBeTrue();
    });
});

describe('lookups', function (): void {
    it('returns the catalogue line', function (): void {
        bootTranslator(['en_US' => ['Save' => 'Enregistrer']]);

        expect(gateway()->translate('Save', [], 'en_US'))->toBe('Enregistrer');
    });

    it('returns null for a key the catalogue does not hold', function (): void {
        bootTranslator(['en_US' => ['Save' => 'Enregistrer']]);

        expect(gateway()->translate('Missing', [], 'en_US'))->toBeNull();
    });

    it('applies replacements through Laravel itself', function (): void {
        bootTranslator(['en_US' => ['Shipping :brand' => 'Ships via :brand (:BRAND)']]);

        expect(gateway()->translate('Shipping :brand', ['brand' => 'dhl'], 'en_US'))
            ->toBe('Ships via dhl (DHL)');
    });

    it('honours a handler registered through Lang::stringable()', function (): void {
        $translator = bootTranslator(['en_US' => ['Sent on :date' => 'Sent on :date']]);
        $translator->stringable(DateTimeImmutable::class, fn (DateTimeImmutable $d): string => $d->format('Y'));

        expect(gateway()->translate('Sent on :date', ['date' => new DateTimeImmutable('2026-01-01')], 'en_US'))
            ->toBe('Sent on 2026');
    });

    // __() is typed against a string return, as WordPress's own __() is. A group
    // key resolves to an array, which callers must never receive.
    it('returns null rather than an array for a group key', function (): void {
        bootTranslator(groups: ['en_US' => ['validation' => ['required' => 'Required']]]);

        expect(gateway()->translate('validation', [], 'en_US'))->toBeNull();
    });

    it('still returns a scalar line held inside a group', function (): void {
        bootTranslator(groups: ['en_US' => ['validation' => ['required' => 'Required']]]);

        expect(gateway()->translate('validation.required', [], 'en_US'))->toBe('Required');
    });
});

// A key that WordPress owns is not a missing Laravel translation. Translator::has()
// suppresses the handlers for the duration of its lookup, and get() only runs once
// the key is known to resolve — so the handler must never see these keys.
it('does not report a WordPress-owned key as a missing Laravel translation', function (): void {
    $translator = bootTranslator(['en_US' => ['Save' => 'Enregistrer']]);

    $missing = [];
    $translator->handleMissingKeysUsing(function (string $key) use (&$missing): void {
        $missing[] = $key;
    });

    gateway()->translate('Add to cart', [], 'en_US');

    expect($missing)->toBe([]);
});
