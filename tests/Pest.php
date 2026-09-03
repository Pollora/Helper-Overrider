<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Pollora\HelperOverrider\Tests\Doubles\WordPressState;

uses()
    ->beforeEach(function (): void {
        WordPressState::reset();
        clearFacadeApplication();
    })
    ->afterEach(function (): void {
        clearFacadeApplication();
    })
    ->in('Unit');

/**
 * Bind a real Laravel translator onto a real application and make it the facade root.
 *
 * @param  array<string, array<string, string>>  $json  Lines keyed by locale then key, as a `lang/<locale>.json` file would hold them.
 * @param  array<string, array<string, array<string, mixed>>>  $groups  Group files keyed by locale then group name.
 */
function bootTranslator(array $json = [], string $locale = 'en_US', array $groups = []): Translator
{
    $loader = new ArrayLoader;

    foreach ($json as $jsonLocale => $lines) {
        // '*' / '*' is where Translator::get() looks for JSON translations,
        // which is the path __() exercises.
        $loader->addMessages($jsonLocale, '*', $lines, '*');
    }

    foreach ($groups as $groupLocale => $files) {
        foreach ($files as $group => $lines) {
            $loader->addMessages($groupLocale, $group, $lines);
        }
    }

    $translator = new Translator($loader, $locale);

    $app = bootApplication();
    $app->instance('translator', $translator);

    return $translator;
}

/**
 * A bare application instance registered as the facade root.
 */
function bootApplication(): Application
{
    $app = new Application(__DIR__);

    Facade::setFacadeApplication($app);

    return $app;
}

/**
 * Detach any application left over from a previous test.
 */
function clearFacadeApplication(): void
{
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    Container::setInstance();
}
