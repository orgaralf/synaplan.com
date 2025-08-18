<?php

declare(strict_types=1);

namespace Codewithkyrian\PlatformPackageInstaller;

use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\PartialComposer;
use React\Promise\PromiseInterface;

class PlatformInstaller extends LibraryInstaller
{
    public function __construct(IOInterface $io, PartialComposer $composer)
    {
        parent::__construct($io, $composer, "platform-package");
    }

    public function download(PackageInterface $package, ?PackageInterface $prevPackage = null): ?PromiseInterface
    {
        if ($url = $this->resolveDistUrl($package)) {
            $package->setDistUrl($url);
            $package->setDistType($this->inferArchiveType($url));
        }

        return parent::download($package, $prevPackage);
    }

    private function resolveDistUrl(PackageInterface $package): string|false
    {
        $platformUrls = $package->getExtra()['platform-urls'] ?? [];
        $platformUrls = $this->validatePlatformUrls($package, $platformUrls);

        if ($matchingUrl = Platform::findBestMatch($platformUrls)) {
            // Check if the URL exists
            if ($this->urlExists($matchingUrl)) {
                return $matchingUrl;
            }

            $this->io->writeError("{$package->getName()}: URL found for current platform but it doesn't exist: $matchingUrl");
            return false;
        }

        $this->io->writeError("{$package->getName()}: No download URL found for current platform");
        return false;
    }

    /**
     * Check if a URL exists by sending a HEAD request
     */
    private function urlExists(string $url): bool
    {
        try {
            $headers = @get_headers($url);

            if ($headers === false) {
                return false;
            }

            return str_contains($headers[0], '200') || str_contains($headers[0], '302');
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @param PackageInterface $package
     * @param array<string, string> $platformUrls
     *
     * @return array<string, string>
     */
    private function validatePlatformUrls(PackageInterface $package, array $platformUrls): array
    {
        $validatedPlatforms = [];
        foreach ($platformUrls as $platform => $url) {
            assert(is_string($url), 'Platform URL must be a string');

            $processedUrl = str_replace('{version}', $package->getPrettyVersion(), $url);

            if (!filter_var($processedUrl, FILTER_VALIDATE_URL)) {
                $this->io->writeError("{$package->getName()}: Invalid URL : $processedUrl. Skipping...");
                continue;
            }

            $validatedPlatforms[strtolower($platform)] = $processedUrl;
        }

        return $validatedPlatforms;
    }

    private function inferArchiveType(string $url): string
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));

        $archiveTypes = [
            // Compressed archives
            'zip' => 'zip',
            'tar' => 'tar',
            'gz' => 'tar',
            'tgz' => 'tar',
            'tbz2' => 'tar',
            'bz2' => 'tar',
            '7z' => '7z',
            'rar' => 'rar',

            // Less common but still valid
            'xz' => 'tar',
            'lz' => 'tar',
            'lzma' => 'tar',
        ];

        if (isset($archiveTypes[$extension])) {
            return $archiveTypes[$extension];
        }

        try {
            $headers = get_headers($url, true);

            if (is_array($headers)) {
                $contentType = strtolower($headers['Content-Type'] ?? '');

                // Common content type mappings
                $contentTypeMap = [
                    'application/zip' => 'zip',
                    'application/x-zip-compressed' => 'zip',
                    'application/x-tar' => 'tar',
                    'application/x-gzip' => 'tar',
                    'application/gzip' => 'tar',
                    'application/x-bzip2' => 'tar',
                ];

                foreach ($contentTypeMap as $type => $archiveType) {
                    if (strpos($contentType, $type) !== false) {
                        return $archiveType;
                    }
                }
            }
        } catch (\Exception) {
        }

        // Fallback to ZIP if no other type could be determined
        return 'zip';
    }
}
