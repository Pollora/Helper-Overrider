<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

/*
 * ARCH-07: the harness preset (WordPress classes and functions, the tests), plus what is specific to this package.
 */
$configuration = require __DIR__.'/vendor/pollora/harness/presets/composer-dependency-analyser.php';

return $configuration
    // The package declares no dependency on purpose, so it stays ahead of laravel/framework in Composer's load order.
    // The Laravel side of __() is used only once the facade class exists, and laravel/framework is a dev dependency.
    ->ignoreErrorsOnPackage('laravel/framework', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
