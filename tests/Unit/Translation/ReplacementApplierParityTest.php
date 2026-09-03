<?php

declare(strict_types=1);

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Pollora\HelperOverrider\Tests\Doubles\Suit;
use Pollora\HelperOverrider\Tests\Doubles\Weekday;
use Pollora\HelperOverrider\Translation\ReplacementApplier;

/**
 * ReplacementApplier reimplements Translator::makeReplacements(), which is
 * protected and therefore unreachable — and which has to keep working on the
 * WordPress-fallback path, where the line comes from a gettext catalogue and
 * there may be no Laravel application at all.
 *
 * These tests diff the two implementations against a real Translator, so that
 * a change in Laravel's substitution semantics fails here rather than silently
 * rendering different strings on the two paths.
 */

/**
 * Run a line through the real Laravel translator by planting it in the catalogue.
 *
 * @param  array<array-key, mixed>  $replace
 */
function laravelReplacement(string $line, array $replace): string
{
    $loader = new ArrayLoader;
    $loader->addMessages('en', '*', ['parity-key' => $line], '*');

    $result = (new Translator($loader, 'en'))->get('parity-key', $replace);

    if (! is_string($result)) {
        throw new RuntimeException('The parity fixture expects Laravel to return a single line.');
    }

    return $result;
}

dataset('replacement shapes', [
    'plain placeholder' => ['Shipping :brand', ['brand' => 'Colissimo']],
    'case variants' => [':name / :Name / :NAME', ['name' => 'olivier']],
    'multibyte capitalisation' => [':Name and :NAME', ['name' => 'élodie']],
    'repeated placeholder' => [':a and :a again', ['a' => 'x']],
    'unmatched placeholder' => ['Shipping :brand to :city', ['brand' => 'DHL']],
    'null value' => ['Shipping :brand.', ['brand' => null]],
    'integer value' => [':count items', ['count' => 3]],
    'float value' => [':price EUR', ['price' => 4.5]],
    'true value' => ['flag=:flag.', ['flag' => true]],
    'false value' => ['flag=:flag.', ['flag' => false]],
    'zero value' => ['n=:n.', ['n' => 0]],
    'empty string value' => ['n=:n.', ['n' => '']],
    'backed enum' => ['Suit: :suit', ['suit' => Suit::Hearts]],
    'pure enum' => ['Day: :day', ['day' => Weekday::Monday]],
    'integer key' => ['Item :0', [0 => 'first']],
    'overlapping keys' => [':a :ab', ['a' => 'X', 'ab' => 'Y']],
    'value containing a placeholder' => [':a', ['a' => ':b', 'b' => 'nested']],
    'no replacements' => ['Nothing to do', []],
    'uppercase key' => [':NAME', ['NAME' => 'olivier']],
]);

it('matches Laravel byte for byte', function (string $line, array $replace): void {
    expect((new ReplacementApplier)->apply($line, $replace))
        ->toBe(laravelReplacement($line, $replace));
})->with('replacement shapes');

it('matches Laravel on closure spans', function (): void {
    $line = 'Read the <link>docs</link> now';
    $replace = ['link' => fn (string $text): string => '<a href="/d">'.$text.'</a>'];

    expect((new ReplacementApplier)->apply($line, $replace))
        ->toBe(laravelReplacement($line, $replace));
});

it('matches Laravel when a closure span and a placeholder are mixed', function (): void {
    $line = 'Hi :name, see <link>this</link>';
    $replace = ['name' => 'Olivier', 'link' => strtoupper(...)];

    expect((new ReplacementApplier)->apply($line, $replace))
        ->toBe(laravelReplacement($line, $replace));
});

it('matches Laravel on a Stringable value', function (): void {
    $brand = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'Chronopost';
        }
    };

    expect((new ReplacementApplier)->apply('Shipping :brand', ['brand' => $brand]))
        ->toBe(laravelReplacement('Shipping :brand', ['brand' => $brand]));
});
