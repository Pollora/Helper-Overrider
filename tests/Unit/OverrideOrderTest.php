<?php

declare(strict_types=1);

/**
 * The whole package rests on one fragile property: its `src/helpers.php` has to
 * be the first `__()` definition Composer loads.
 *
 * `laravel/framework` declares its own `__()` behind the same
 * `function_exists()` guard, and Composer emits `autoload.files` in dependency
 * order — dependencies first. The package therefore only wins the race while it
 * has no dependency that would sort it after `laravel/framework`. Adding one
 * (an `illuminate/*` require, say) does not fail anything loudly: `__()` simply
 * becomes Laravel's again, WordPress catalogues stop resolving, and every core
 * or plugin string silently falls back to its untranslated form.
 *
 * These tests are the alarm for that.
 */

/**
 * @return array{file: string, result: string}
 */
function runOverrideFixture(string $mode): array
{
    $output = shell_exec(sprintf(
        '%s %s %s',
        escapeshellarg(PHP_BINARY),
        escapeshellarg(__DIR__.'/../Fixtures/override-order.php'),
        escapeshellarg($mode),
    ));

    $decoded = json_decode((string) $output, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($decoded) || ! is_string($decoded['file'] ?? null) || ! is_string($decoded['result'] ?? null)) {
        throw new RuntimeException('The fixture process did not report a usable result: '.var_export($output, true));
    }

    return ['file' => $decoded['file'], 'result' => $decoded['result']];
}

it('owns __() when loaded in Composer dependency order', function (): void {
    $run = runOverrideFixture('package');

    expect($run['file'])->toEndWith('src'.DIRECTORY_SEPARATOR.'helpers.php')
        ->and($run['result'])->toBe('Shipping Test');
});

// The failure this guards against, made visible: with laravel/framework's
// helpers loaded first, __() is Laravel's and the WordPress side is gone.
it('loses __() to laravel/framework when it is loaded second', function (): void {
    $run = runOverrideFixture('vendor-first');

    expect($run['file'])->toContain('laravel'.DIRECTORY_SEPARATOR.'framework');
});

it('declares no runtime dependency that would reorder it after laravel/framework', function (): void {
    $manifest = json_decode(
        (string) file_get_contents(__DIR__.'/../../composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest)->toBeArray()
        ->and(is_array($manifest) ? $manifest['require'] : null)->toBe(['php' => '^8.3']);
});
