<?php

declare(strict_types=1);

// Loaded before the autoloader on purpose. Both this package and
// laravel/framework declare __() behind a function_exists() guard, so whichever
// file Composer loads first wins. In an application, the dependency graph puts
// this package ahead of laravel/framework; under the package's own test suite it
// is the root package, whose autoload.files come last. Requiring it here
// restores production ordering. The classes it references are resolved through
// PSR-4 when __() is first called, well after the autoloader is up.
require_once __DIR__.'/../src/helpers.php';

require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/wordpress-functions.php';
