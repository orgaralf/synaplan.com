<?php

declare(strict_types=1);

namespace Codewithkyrian\PlatformPackageInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable, CommandProvider
{
    protected Composer $composer;

    protected IOInterface $io;

    /**
     * Activate the plugin
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $composer->getInstallationManager()->addInstaller(new PlatformInstaller($io, $composer));
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $composer->getInstallationManager()->removeInstaller(new PlatformInstaller($io, $composer));
    }

    public function uninstall(Composer $composer, IOInterface $io) {}

    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => self::class
        ];
    }

    public function getCommands(): array
    {
        return [
            new GenerateUrlCommand()
        ];
    }
}
