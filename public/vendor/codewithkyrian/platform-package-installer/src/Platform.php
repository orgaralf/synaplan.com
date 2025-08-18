<?php

declare(strict_types=1);

namespace Codewithkyrian\PlatformPackageInstaller;

class Platform
{
    /**
     * Get detailed current platform information
     */
    public static function current(): array
    {
        $osName = strtolower(php_uname('s'));
        $arch = php_uname('m');

        $normalizedArch = self::normalizeArchitecture($arch);

        return [
            'os' => $osName,
            'arch' => $normalizedArch,
            'full' => php_uname()
        ];
    }

    /**
     * Normalize architecture names for consistent matching
     */
    public static function normalizeArchitecture(string $arch): string
    {
        $arch = strtolower($arch);

        // Common architecture normalization
        $archMap = [
            // x86 family
            'x86_64' => 'x86_64',
            'amd64' => 'x86_64',
            'i386' => 'x86',
            'i686' => 'x86',
            'x64' => 'x86_64',
            'x86' => 'x86',
            '32' => 'x86',
            '64' => 'x86_64',

            // ARM family
            'arm64' => 'arm64',
            'aarch64' => 'arm64',
            'armv7' => 'arm',
            'armv8' => 'arm64',
            'arm64v8' => 'arm64',

            // Other architectures
            'ppc64' => 'ppc64',
            'ppc64le' => 'ppc64le',
            's390x' => 's390x'
        ];

        return $archMap[$arch] ?? $arch;
    }

    /**
     * Check if a platform definition matches the current platform
     */
    public static function matches(string $definedPlatform, ?array $currentPlatform = null): bool
    {
        $currentPlatform = $currentPlatform ?? self::current();
        $definedPlatform = strtolower($definedPlatform);

        // Exact match for all platforms
        if ($definedPlatform === 'all') {
            return true;
        }

        // Split platform into OS and optional architecture
        $parts = explode('-', $definedPlatform);
        $os = $parts[0];
        $arch = count($parts) > 1 ? $parts[1] : null;

        // OS matching with flexible mapping
        $osMatch = self::matchesOS($os, $currentPlatform['os']);

        // Architecture matching (if specified)
        $archMatch = $arch === null ||
            self::normalizeArchitecture($arch) === $currentPlatform['arch'];

        return $osMatch && $archMatch;
    }

    /**
     * More flexible OS matching
     */
    private static function matchesOS(string $definedOs, string $currentOs): bool
    {
        $osAliases = [
            'windows' => ['windows', 'win32', 'win64', 'windows nt'],
            'darwin' => ['macos', 'mac', 'darwin'],
            'linux' => ['linux', 'gnu/linux'],
            'raspberrypi' => ['raspbian', 'raspberry pi']
        ];

        // Exact match
        if ($definedOs === $currentOs) {
            return true;
        }

        // Check aliases
        foreach ($osAliases as $alias => $variations) {
            if ($definedOs === $alias && in_array($currentOs, $variations)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the most appropriate match for the current platform from a given set of platform-specific entries.
     *
     * @template T
     *
     * @param array<string, T> $platformEntries Associative array with platform identifiers as keys
     * @param array{os: string, arch: string}|null $currentPlatform Platform to match against (defaults to current system)
     *
     * @return T|false The matching entry, or false if no match is found
     */
    public static function findBestMatch(array $platformEntries, array $currentPlatform = null): mixed
    {
        $currentPlatform = $currentPlatform ?? self::current();

        $matchingPlatforms = array_filter(
            array_keys($platformEntries),
            fn ($platform) => self::matches($platform, $currentPlatform)
        );

        $prioritizedMatches = self::prioritizePlatformMatches($matchingPlatforms);

        foreach ($prioritizedMatches as $platform) {
            return $platformEntries[$platform];
        }

        return false;
    }

    /**
     * Prioritize platform matches to prefer more specific matches
     *
     * @param array $matches
     *
     * @return array
     */
    private static function prioritizePlatformMatches(array $matches): array
    {
        usort($matches, function ($a, $b) {
            // 'all' is the least specific
            if ($a === 'all') return 1;
            if ($b === 'all') return -1;

            // Platforms with architecture are more specific than OS-only
            $aHasArch = str_contains($a, '-');
            $bHasArch = str_contains($b, '-');

            if ($aHasArch && !$bHasArch) return -1;
            if (!$aHasArch && $bHasArch) return 1;

            return 0;
        });

        return $matches;
    }

    public static function isWindows(): bool
    {
        return \defined('PHP_WINDOWS_VERSION_BUILD');
    }
}
