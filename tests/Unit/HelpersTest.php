<?php

declare(strict_types=1);

use Pollora\HelperOverrider\Tests\Doubles\WordPressState;
use Pollora\HelperOverrider\Translation\TranslationResolver;

/**
 * End-to-end coverage of the wiring behind `__()`: the real gateways, a real
 * Laravel translator behind the facade, and the WordPress function doubles
 * standing in for core.
 *
 * These go through `pollora_translation_resolver()` rather than the global
 * `__()` because Pest requires the autoloader from its own binary, so
 * laravel/framework's `__()` is always installed by the time the suite runs.
 * That `__()` itself resolves to this package is proven separately, in a clean
 * process, by OverrideOrderTest.
 */

/**
 * @param  array<array-key, mixed>|string  $replace
 */
function translateHelper(string $key, array|string $replace = [], ?string $locale = null): string
{
    return pollora_translation_resolver()->translate($key, $replace, $locale);
}

it('exposes a shared resolver', function (): void {
    expect(pollora_translation_resolver())
        ->toBeInstanceOf(TranslationResolver::class)
        ->toBe(pollora_translation_resolver());
});

// The regression this package was fixed for: sources are in English, the site
// is in English, so there is no en_US.json at all and Lang::has() answers no.
// The replacement array then travelled into WordPress's translate() as a text
// domain and fataled on "Cannot access offset of type array in isset or empty".
it('renders a placeholder call whose key is in no catalogue', function (): void {
    bootTranslator(locale: 'en_US');
    WordPressState::$locale = 'en_US';

    expect(translateHelper('Shipping :brand', ['brand' => 'Test']))->toBe('Shipping Test');
});

it('never passes a non-string text domain to WordPress', function (): void {
    bootTranslator(locale: 'en_US');

    translateHelper('Shipping :brand', ['brand' => 'Test']);

    expect(WordPressState::$translateCalls)->not->toBeEmpty();

    foreach (WordPressState::$translateCalls as [, $domain]) {
        expect($domain)->toBeString();
    }
});

it('serves a placeholder call from the Laravel catalogue when it is there', function (): void {
    bootTranslator(['fr_FR' => ['Shipping :brand' => 'Livraison :brand']], 'fr_FR');
    WordPressState::$locale = 'fr_FR';

    expect(translateHelper('Shipping :brand', ['brand' => 'Colissimo']))->toBe('Livraison Colissimo');
});

it('fills placeholders into a line that only WordPress knows', function (): void {
    bootTranslator(locale: 'fr_FR');
    WordPressState::$locale = 'fr_FR';
    WordPressState::$catalogue = ['default' => ['Shipping :brand' => 'Livraison via :brand']];

    expect(translateHelper('Shipping :brand', ['brand' => 'Colissimo']))->toBe('Livraison via Colissimo');
});

it('keeps working with no application booted at all', function (): void {
    WordPressState::$catalogue = ['default' => ['Save' => 'Enregistrer']];

    expect(translateHelper('Save'))->toBe('Enregistrer')
        ->and(translateHelper('Shipping :brand', ['brand' => 'Test']))->toBe('Shipping Test');
});

it('routes a string second argument to the named WordPress domain', function (): void {
    bootTranslator(['en_US' => ['Save' => 'Laravel line']]);
    WordPressState::$catalogue = ['my-plugin' => ['Save' => 'Sauvegarder']];

    expect(translateHelper('Save', 'my-plugin'))->toBe('Sauvegarder');
});

it('prefers Laravel over the WordPress default domain for a bare call', function (): void {
    bootTranslator(['en_US' => ['Save' => 'Laravel line']]);
    WordPressState::$catalogue = ['default' => ['Save' => 'WordPress line']];

    expect(translateHelper('Save'))->toBe('Laravel line');
});

it('always returns a string, even for a Laravel group key', function (): void {
    bootTranslator(groups: ['en_US' => ['validation' => ['required' => 'Required']]]);

    expect(translateHelper('validation'))->toBeString()->toBe('validation');
});

it('honours an explicitly passed locale', function (): void {
    bootTranslator(['de_DE' => ['Save' => 'Speichern']], 'en_US');

    expect(translateHelper('Save', [], 'de_DE'))->toBe('Speichern');
});
