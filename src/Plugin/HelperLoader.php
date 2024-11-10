<?php

declare(strict_types=1);

namespace Pollora\HelperOverrider\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use RuntimeException;

final class HelperLoader implements PluginInterface, EventSubscriberInterface
{
    private const PACKAGE_NAME = 'pollora/framework';

    public function __construct(
        private ?Composer $composer = null,
        private ?IOInterface $io = null
    ) {}

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Cleanup if needed
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Cleanup if needed
    }

    /**
     * Get the subscribed events for this plugin.
     *
     * @return array The list of events this plugin is listening to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump'
        ];
    }

    /**
     * This method is called after the autoload dump is generated.
     * It is responsible for loading helper files into the autoloader.
     *
     * @throws RuntimeException If the autoload file is not found or if an error occurs during the process.
     */
    public function onPostAutoloadDump(): void
    {
        $this->log('info', 'Loading helpers...');

        try {
            $this->processHelperInjection();
        } catch (RuntimeException $e) {
            $this->log('error', $e->getMessage());
        }
    }

    /**
     * This method is responsible for loading helper files into the autoloader.
     * It first resolves the helper files and then injects them into the autoload file.
     *
     * @throws RuntimeException If the autoload file is not found or if an error occurs during the process.
     */
    private function processHelperInjection(): void
    {
        $autoloadFile = $this->resolveAutoloadPath();

        if (!is_file($autoloadFile)) {
            throw new RuntimeException('Autoload file not found');
        }

        $helpers = $this->resolveHelperFiles();

        if (empty($helpers)) {
            $this->log('comment', 'No helper files found in composer.json');
            return;
        }

        $this->injectHelpers($autoloadFile, $helpers);
        $this->log('info', 'Helper files injected successfully');
    }

    /**
     * This method resolves the helper files by finding the package and extracting the relevant files from the autoload section.
     *
     * @return array The list of helper files to be injected into the autoloader.
     */
    private function resolveHelperFiles(): array
    {
        $package = $this->findPackage();

        if (!$package) {
            return [];
        }

        $autoload = $package->getAutoload();
        $files = $autoload['files'] ?? [];



        return array_map(
            static function(string $file): string {
                return sprintf("__DIR__ . '/..' . '/%s/%s'", self::PACKAGE_NAME, $file);
            },
            $files
        );
    }

    /**
     * This method finds the package by searching through the local repository.
     *
     * @return \Composer\Package\PackageInterface|null The package if found, null otherwise.
     */
    private function findPackage(): ?\Composer\Package\PackageInterface
    {
        $localRepository = $this->composer?->getRepositoryManager()->getLocalRepository();

        if (!$localRepository) {
            return null;
        }

        foreach ($localRepository->getPackages() as $package) {
            if ($package->getName() === self::PACKAGE_NAME) {
                return $package;
            }
        }

        return null;
    }

    /**
     * This method resolves the autoload path by extracting it from the composer configuration.
     *
     * @return string The path to the autoload file.
     */
    private function resolveAutoloadPath(): string
    {
        $vendorDir = $this->composer?->getConfig()->get('vendor-dir');

        if (!$vendorDir) {
            throw new RuntimeException('Unable to determine vendor directory');
        }

        return sprintf('%s/composer/autoload_static.php', $vendorDir);
    }

    /**
     * This method injects the helper files into the autoload file by updating the files array.
     *
     * @param string $autoloadFile The path to the autoload file.
     * @param array $helperFiles The list of helper files to be injected.
     *
     * @throws RuntimeException If the autoload file cannot be updated.
     */
    private function injectHelpers(string $autoloadFile, array $helperFiles): void
    {
        $content = file_get_contents($autoloadFile);

        if (!preg_match('/public static \$files = array \((.*?)\);/s', $content, $matches)) {
            throw new RuntimeException('Unable to locate files array in autoload file');
        }

        $newContent = $this->reorganizeAutoloadContent($matches[1], $helperFiles);

        $updatedContent = preg_replace(
            '/public static \$files = array \((.*?)\);/s',
            sprintf('public static $files = array (%s);', $newContent),
            $content
        );

        if ($updatedContent === null) {
            throw new RuntimeException('Failed to update autoload content');
        }

        file_put_contents($autoloadFile, $updatedContent);
    }

    /**
     * This method reorganizes the files array by prioritizing the helper files.
     *
     * @param string $filesContent The content of the files array.
     * @param array $helperFiles The list of helper files to be prioritized.
     *
     * @return string The reorganized files array content.
     */
    private function reorganizeAutoloadContent(string $filesContent, array $helperFiles): string
    {
        if (!preg_match_all("/'([^']+)' => ([^,]+),/", $filesContent, $entries)) {
            return $filesContent;
        }

        $reorganizedEntries = $this->prioritizeHelperEntries($entries, $helperFiles);

        return "\n        " . implode("\n        ", array_filter($reorganizedEntries)) . "\n    ";
    }

    /**
     * This method prioritizes the helper files by inserting them before other files.
     *
     * @param array $entries The list of entries in the files array.
     * @param array $helperFiles The list of helper files to be prioritized.
     *
     * @return array The reorganized list of entries in the files array.
     */
    private function prioritizeHelperEntries(array $entries, array $helperFiles): array
    {
        $prioritizedEntries = [];
        $remainingEntries = $entries[0];

        // Prioritize helper files
        foreach ($helperFiles as $helperPath) {
            foreach ($remainingEntries as $index => $entry) {
                if (str_contains($entries[2][$index], $helperPath)) {
                    $prioritizedEntries[] = $entry;
                    unset($remainingEntries[$index]);
                }
            }
        }

        return [...array_values($prioritizedEntries), ...array_values($remainingEntries)];
    }

    /**
     * This method logs a message with the specified type and content.
     *
     * @param string $type The type of the message (e.g., 'info', 'error', 'comment').
     * @param string $message The content of the message.
     */
    private function log(string $type, string $message): void
    {
        $this->io?->write(sprintf('<%s>Pollora: %s</%s>', $type, $message, $type));
    }
}
