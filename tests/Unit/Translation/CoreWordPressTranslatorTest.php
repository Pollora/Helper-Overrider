<?php

declare(strict_types=1);

use Pollora\HelperOverrider\Tests\Doubles\WordPressState;
use Pollora\HelperOverrider\Translation\CoreWordPressTranslator;

function wordpressGateway(): CoreWordPressTranslator
{
    return new CoreWordPressTranslator;
}

// The test suite defines the WordPress function doubles globally, so the
// "WordPress absent" branch cannot be reached from here; the resolver tests
// cover it through FakeWordPressTranslator instead.
it('reports available when WordPress gettext functions exist', function (): void {
    expect(wordpressGateway()->isAvailable())->toBeTrue();
});

it('translates through the requested text domain', function (): void {
    WordPressState::$catalogue = [
        'default' => ['Save' => 'Enregistrer'],
        'my-plugin' => ['Save' => 'Sauvegarder'],
    ];

    expect(wordpressGateway()->translate('Save', 'default'))->toBe('Enregistrer')
        ->and(wordpressGateway()->translate('Save', 'my-plugin'))->toBe('Sauvegarder');
});

it('returns the text unchanged when the domain holds no entry', function (): void {
    expect(wordpressGateway()->translate('Save', 'default'))->toBe('Save');
});

it('reports the WordPress locale', function (): void {
    WordPressState::$locale = 'fr_FR';

    expect(wordpressGateway()->locale())->toBe('fr_FR');
});

it('reports no locale when WordPress returns an empty one', function (): void {
    WordPressState::$locale = '';

    expect(wordpressGateway()->locale())->toBeNull();
});
