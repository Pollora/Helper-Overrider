<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        // src/helpers.php defines global functions guarded by function_exists();
        // Rector's early-return and dead-code passes have nothing useful to say
        // about a file that is deliberately procedural.
        __DIR__.'/src/helpers.php',
        // The WordPress function doubles in the test suite are intentionally
        // minimal stand-ins, not code to be modernised.
        __DIR__.'/tests/wordpress-functions.php',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        codingStyle: true,
    )
    ->withPhpSets();
