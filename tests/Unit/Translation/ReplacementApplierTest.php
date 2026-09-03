<?php

declare(strict_types=1);

use Pollora\HelperOverrider\Tests\Doubles\Suit;
use Pollora\HelperOverrider\Tests\Doubles\Weekday;
use Pollora\HelperOverrider\Translation\ReplacementApplier;

function applier(): ReplacementApplier
{
    return new ReplacementApplier;
}

it('returns the line untouched when there is nothing to replace', function (): void {
    expect(applier()->apply('Shipping :brand', []))->toBe('Shipping :brand');
});

it('substitutes a named placeholder', function (): void {
    expect(applier()->apply('Shipping :brand', ['brand' => 'Colissimo']))
        ->toBe('Shipping Colissimo');
});

it('honours the capitalised and upper-cased placeholder variants', function (): void {
    expect(applier()->apply(':name / :Name / :NAME', ['name' => 'olivier']))
        ->toBe('olivier / Olivier / OLIVIER');
});

it('capitalises with multibyte awareness', function (): void {
    expect(applier()->apply(':Name', ['name' => 'élodie']))->toBe('Élodie');
});

it('replaces every occurrence of a placeholder', function (): void {
    expect(applier()->apply(':a and :a', ['a' => 'x']))->toBe('x and x');
});

it('leaves placeholders it was given no value for', function (): void {
    expect(applier()->apply('Shipping :brand to :city', ['brand' => 'DHL']))
        ->toBe('Shipping DHL to :city');
});

it('renders a null value as an empty string', function (): void {
    expect(applier()->apply('Shipping :brand.', ['brand' => null]))->toBe('Shipping .');
});

it('stringifies numeric values', function (): void {
    expect(applier()->apply(':count items, :price EUR', ['count' => 3, 'price' => 4.5]))
        ->toBe('3 items, 4.5 EUR');
});

it('unwraps a backed enum to its value', function (): void {
    expect(applier()->apply('Suit: :suit', ['suit' => Suit::Hearts]))->toBe('Suit: H');
});

it('unwraps a pure enum to its name', function (): void {
    expect(applier()->apply('Day: :day', ['day' => Weekday::Monday]))->toBe('Day: Monday');
});

it('stringifies an object that can render itself', function (): void {
    $brand = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'Chronopost';
        }
    };

    expect(applier()->apply('Shipping :brand', ['brand' => $brand]))->toBe('Shipping Chronopost');
});

it('runs a closure over the span it delimits', function (): void {
    $result = applier()->apply(
        'Read the <link>documentation</link> first',
        ['link' => fn (string $text): string => '<a href="/docs">'.$text.'</a>'],
    );

    expect($result)->toBe('Read the <a href="/docs">documentation</a> first');
});

it('mixes closure spans and plain placeholders', function (): void {
    $result = applier()->apply(
        'Hi :name, see <link>this</link>',
        ['name' => 'Olivier', 'link' => strtoupper(...)],
    );

    expect($result)->toBe('Hi Olivier, see THIS');
});

// Laravel interpolates the key straight into the pattern; anchoring it through
// preg_quote() keeps a key carrying regex metacharacters from corrupting it.
it('treats a closure key with regex metacharacters literally', function (): void {
    $result = applier()->apply(
        '<a.b>text</a.b>',
        ['a.b' => strtoupper(...)],
    );

    expect($result)->toBe('TEXT');
});

it('accepts integer-like keys', function (): void {
    expect(applier()->apply('Item :0', [0 => 'first']))->toBe('Item first');
});
