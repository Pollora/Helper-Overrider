<?php

declare(strict_types=1);

/**
 * Run in a clean process to observe which `__()` definition wins.
 *
 * Pest requires `vendor/autoload.php` from its own binary, before any
 * bootstrap file runs, so laravel/framework's `__()` is always in place by the
 * time the test suite starts. Reproducing the production load order therefore
 * needs a process the suite does not control.
 *
 * Argument: `package` requires this package's helpers first, as Composer does
 * in an application; `vendor-first` lets laravel/framework win, which is what
 * happens the moment this package gains a dependency on illuminate/*.
 */
$mode = $argv[1] ?? 'package';

if ($mode === 'package') {
    require_once __DIR__.'/../../src/helpers.php';
}

require_once __DIR__.'/../../vendor/autoload.php';

$function = new ReflectionFunction('__');

echo json_encode([
    'file' => $function->getFileName(),
    // With neither a booted application nor WordPress, the package's helper
    // still fills the placeholder; Laravel's own helper needs a container and
    // would raise "A facade root has not been set."
    'result' => (function (): string {
        try {
            return (string) __('Shipping :brand', ['brand' => 'Test']);
        } catch (Throwable $throwable) {
            return 'threw: '.$throwable::class;
        }
    })(),
], JSON_THROW_ON_ERROR);
