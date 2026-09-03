<?php

declare(strict_types=1);

use Pollora\HelperOverrider\Tests\Doubles\FakeLaravelTranslator;
use Pollora\HelperOverrider\Tests\Doubles\FakeWordPressTranslator;
use Pollora\HelperOverrider\Translation\TranslationResolver;

/**
 * @param  array<string, array<string, string>>  $laravel
 * @param  array<string, array<string, string>>  $wordpress
 */
function resolver(
    array $laravel = [],
    array $wordpress = [],
    ?string $locale = 'en_US',
    bool $laravelAvailable = true,
    bool $wordpressAvailable = true,
): TranslationResolver {
    return new TranslationResolver(
        new FakeLaravelTranslator($laravel, $laravelAvailable),
        new FakeWordPressTranslator($wordpress, $locale, $wordpressAvailable),
    );
}

describe('a string second argument is a WordPress text domain', function (): void {
    it('translates through the named domain', function (): void {
        $result = resolver(wordpress: ['my-plugin' => ['Save' => 'Enregistrer']])
            ->translate('Save', 'my-plugin');

        expect($result)->toBe('Enregistrer');
    });

    it('never consults Laravel, so an unrelated key cannot shadow the plugin catalogue', function (): void {
        $laravel = new FakeLaravelTranslator(['en_US' => ['Save' => 'Laravel wins']]);

        $result = (new TranslationResolver($laravel, new FakeWordPressTranslator))
            ->translate('Save', 'my-plugin');

        expect($result)->toBe('Save')
            ->and($laravel->lookups)->toBe([]);
    });

    it('treats an empty domain as the default one', function (): void {
        $wordpress = new FakeWordPressTranslator;

        (new TranslationResolver(new FakeLaravelTranslator, $wordpress))->translate('Save', '');

        expect($wordpress->lookups)->toBe([['Save', 'default']]);
    });

    it('keeps "0" as a usable domain name rather than folding it into the default', function (): void {
        $wordpress = new FakeWordPressTranslator;

        (new TranslationResolver(new FakeLaravelTranslator, $wordpress))->translate('Save', '0');

        expect($wordpress->lookups)->toBe([['Save', '0']]);
    });
});

describe('a non-empty array second argument is a Laravel call', function (): void {
    it('resolves from the Laravel catalogue when the key is there', function (): void {
        $result = resolver(laravel: ['en_US' => ['Shipping :brand' => 'Ships via :brand']])
            ->translate('Shipping :brand', ['brand' => 'Test']);

        expect($result)->toBe('Ships via Test');
    });

    // The reported crash: the replacement array used to reach WordPress's
    // translate() as a text domain, where get_translations_for_domain() ran
    // isset($l10n[$domain]) on it and raised
    // "Cannot access offset of type array in isset or empty".
    it('does not hand the replacement array to WordPress when the key is missing', function (): void {
        $wordpress = new FakeWordPressTranslator;

        $result = (new TranslationResolver(new FakeLaravelTranslator, $wordpress))
            ->translate('Shipping :brand', ['brand' => 'Test']);

        expect($result)->toBe('Shipping Test')
            ->and($wordpress->lookups)->toBe([['Shipping :brand', 'default']]);
    });

    it('falls back to the WordPress line and still fills the placeholders', function (): void {
        $result = resolver(
            wordpress: ['default' => ['Shipping :brand' => 'Livraison via :brand']],
            locale: 'fr_FR',
        )->translate('Shipping :brand', ['brand' => 'Colissimo']);

        expect($result)->toBe('Livraison via Colissimo');
    });

    it('renders placeholders even with no catalogue at all on either side', function (): void {
        $result = resolver(laravelAvailable: false, wordpressAvailable: false)
            ->translate('Shipping :brand', ['brand' => 'Test']);

        expect($result)->toBe('Shipping Test');
    });
});

describe('an empty array second argument is ambiguous', function (): void {
    it('prefers the Laravel catalogue', function (): void {
        $result = resolver(
            laravel: ['en_US' => ['Save' => 'Laravel line']],
            wordpress: ['default' => ['Save' => 'WordPress line']],
        )->translate('Save');

        expect($result)->toBe('Laravel line');
    });

    it('falls back to the WordPress default domain', function (): void {
        $result = resolver(wordpress: ['default' => ['Save' => 'WordPress line']])
            ->translate('Save');

        expect($result)->toBe('WordPress line');
    });

    it('returns the key untouched when neither side knows it', function (): void {
        expect(resolver()->translate('Save'))->toBe('Save');
    });
});

describe('the wordpress. prefix opts a key out of Laravel', function (): void {
    it('skips the Laravel lookup entirely and strips the prefix', function (): void {
        $laravel = new FakeLaravelTranslator(['en_US' => ['wordpress.Save' => 'Laravel line']]);
        $wordpress = new FakeWordPressTranslator(['default' => ['Save' => 'WordPress line']]);

        $result = (new TranslationResolver($laravel, $wordpress))->translate('wordpress.Save');

        expect($result)->toBe('WordPress line')
            ->and($laravel->lookups)->toBe([]);
    });

    // A plain str_replace() rewrote the substring wherever it appeared.
    it('only strips the prefix, never a match inside the string', function (): void {
        $wordpress = new FakeWordPressTranslator;

        (new TranslationResolver(new FakeLaravelTranslator, $wordpress))
            ->translate('Go to wordpress.org');

        expect($wordpress->lookups)->toBe([['Go to wordpress.org', 'default']]);
    });
});

describe('locale resolution', function (): void {
    it('asks WordPress for the locale when none is given', function (): void {
        $laravel = new FakeLaravelTranslator;

        (new TranslationResolver($laravel, new FakeWordPressTranslator(locale: 'fr_FR')))
            ->translate('Save');

        expect($laravel->lookups)->toBe([['Save', 'fr_FR'], ['Save', 'fr']]);
    });

    it('prefers an explicitly passed locale over the WordPress one', function (): void {
        $laravel = new FakeLaravelTranslator;

        (new TranslationResolver($laravel, new FakeWordPressTranslator(locale: 'fr_FR')))
            ->translate('Save', [], 'de_DE');

        expect($laravel->lookups)->toBe([['Save', 'de_DE'], ['Save', 'de']]);
    });

    // WordPress reports fr_FR; Laravel projects conventionally ship lang/fr.json,
    // and Laravel's JSON lookup matches the locale exactly with no fallback.
    it('falls back from the regional locale to the base language', function (): void {
        $result = resolver(laravel: ['fr' => ['Save' => 'Enregistrer']], locale: 'fr_FR')
            ->translate('Save');

        expect($result)->toBe('Enregistrer');
    });

    it('lets the regional catalogue win over the base language', function (): void {
        $result = resolver(
            laravel: ['fr_FR' => ['Save' => 'Enregistrer'], 'fr' => ['Save' => 'Sauver']],
            locale: 'fr_FR',
        )->translate('Save');

        expect($result)->toBe('Enregistrer');
    });

    it('normalises a hyphenated locale when deriving the base language', function (): void {
        $laravel = new FakeLaravelTranslator;

        (new TranslationResolver($laravel, new FakeWordPressTranslator))->translate('Save', [], 'pt-BR');

        expect($laravel->lookups)->toBe([['Save', 'pt-BR'], ['Save', 'pt']]);
    });

    it('does not duplicate a locale that has no regional part', function (): void {
        $laravel = new FakeLaravelTranslator;

        (new TranslationResolver($laravel, new FakeWordPressTranslator))->translate('Save', [], 'fr');

        expect($laravel->lookups)->toBe([['Save', 'fr']]);
    });

    it("defers to Laravel's own default locale when none can be determined", function (): void {
        $laravel = new FakeLaravelTranslator;

        (new TranslationResolver($laravel, new FakeWordPressTranslator(locale: null)))->translate('Save');

        expect($laravel->lookups)->toBe([['Save', null]]);
    });
});

describe('availability guards', function (): void {
    it('goes straight to WordPress when no Laravel application is reachable', function (): void {
        $laravel = new FakeLaravelTranslator(['en_US' => ['Save' => 'Laravel line']], available: false);

        $result = (new TranslationResolver($laravel, new FakeWordPressTranslator(['default' => ['Save' => 'WordPress line']])))
            ->translate('Save');

        expect($result)->toBe('WordPress line')
            ->and($laravel->lookups)->toBe([]);
    });

    it('returns the key when WordPress is not loaded either', function (): void {
        expect(resolver(laravelAvailable: false, wordpressAvailable: false)->translate('Save'))
            ->toBe('Save');
    });
});
