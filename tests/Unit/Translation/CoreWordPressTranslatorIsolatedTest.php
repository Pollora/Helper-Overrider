<?php

declare(strict_types=1);

/**
 * Run a script in a fresh PHP process, where only the functions it declares exist.
 *
 * The suite defines the WordPress doubles globally, so the partial-WordPress
 * branches can only be reached from outside it.
 */
function runWithoutWordPress(string $declarations, string $call): string
{
    $root = dirname(__DIR__, 3);
    $script = tempnam(sys_get_temp_dir(), 'ho');

    file_put_contents($script, "<?php\n".
        "require '".$root."/vendor/autoload.php';\n".
        $declarations."\n".
        '$t = new Pollora\HelperOverrider\Translation\CoreWordPressTranslator;'."\n".
        'echo json_encode('.$call.");\n");

    $output = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' 2>&1');
    unlink($script);

    return (string) $output;
}

it('has no locale when get_locale() exists but the object cache does not', function (): void {
    $out = runWithoutWordPress("function get_locale() { return 'fr_FR'; }", '$t->locale()');

    expect($out)->toBe('null');
});

it('has no locale when the object cache exists but get_locale() does not', function (): void {
    $out = runWithoutWordPress('function wp_cache_get() { return false; }', '$t->locale()');

    expect($out)->toBe('null');
});

it('has no locale when neither function exists', function (): void {
    expect(runWithoutWordPress('', '$t->locale()'))->toBe('null');
});

it('reports a locale when both functions exist', function (): void {
    $out = runWithoutWordPress(
        "function get_locale() { return 'fr_FR'; } function wp_cache_get() { return false; }",
        '$t->locale()',
    );

    expect($out)->toBe('"fr_FR"');
});

it('is unavailable and returns the text untouched without translate()', function (): void {
    $out = runWithoutWordPress('', '[$t->isAvailable(), $t->translate("Save", "default")]');

    expect($out)->toBe('[false,"Save"]');
});
