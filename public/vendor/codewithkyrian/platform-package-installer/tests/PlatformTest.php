<?php

declare(strict_types=1);

namespace Codewithkyrian\PlatformPackageInstaller\Tests;

use Codewithkyrian\PlatformPackageInstaller\Platform;

it('normalizes architecture names correctly', function () {
    expect(Platform::normalizeArchitecture('x86_64'))->toBe('x86_64')
        ->and(Platform::normalizeArchitecture('amd64'))->toBe('x86_64')
        ->and(Platform::normalizeArchitecture('aarch64'))->toBe('arm64')
        ->and(Platform::normalizeArchitecture('armv7'))->toBe('arm');
});

it('matches platforms correctly', function () {
    $macArm64 = ['os' => 'darwin', 'arch' => 'arm64', 'full' => 'Darwin Macbook Pro ARM64'];
    $linuxX64 = ['os' => 'linux', 'arch' => 'x86_64', 'full' => 'Linux Ubuntu x86_64'];
    $windowsX32 = ['os' => 'windows', 'arch' => 'x86', 'full' => 'Windows 10 x86'];
    $windowsServer = ['os' => 'windows nt', 'arch' => 'x86_64', 'full' => 'Windows Server x86_64'];

    // Test various platform matches
    expect(Platform::matches('all', $macArm64))->toBeTrue()
        ->and(Platform::matches('darwin', $macArm64))->toBeTrue()
        ->and(Platform::matches('darwin-arm64', $macArm64))->toBeTrue()
        ->and(Platform::matches('darwin-x86_64', $macArm64))->toBeFalse()
        ->and(Platform::matches('linux-x86_64', $linuxX64))->toBeTrue()
        ->and(Platform::matches('linux-arm64', $linuxX64))->toBeFalse()
        ->and(Platform::matches('windows', $windowsX32))->toBeTrue()
        ->and(Platform::matches('windows-32', $windowsX32))->toBeTrue()
        ->and(Platform::matches('windows-64', $windowsX32))->toBeFalse()
        ->and(Platform::matches('windows', $windowsServer))->toBeTrue()
        ->and(Platform::matches('windows-32', $windowsServer))->toBeFalse();
});

it('finds the most appropriate URL for the current platform', function () {
    $platformUrls = [
        'darwin-arm64' => 'https://example.com/darwin-arm64',
        'darwin-x86_64' => 'https://example.com/darwin-x86_64',
        'linux-x86_64' => 'https://example.com/linux-x86_64',
        'windows-32' => 'https://example.com/win-32',
        'windows-64' => 'https://example.com/win-64',
        'windows' => 'https://example.com/win',
        'all' => 'https://example.com/all'
    ];

    $macArm64 = ['os' => 'darwin', 'arch' => 'arm64', 'full' => 'Darwin Macbook Pro ARM64'];
    $linuxX64 = ['os' => 'linux', 'arch' => 'x86_64', 'full' => 'Linux Ubuntu x86_64'];
    $windowsX32 = ['os' => 'windows', 'arch' => 'x86', 'full' => 'Windows 10 x86'];

    expect(Platform::findBestMatch($platformUrls, $macArm64))->toBe('https://example.com/darwin-arm64')
        ->and(Platform::findBestMatch($platformUrls, $linuxX64))->toBe('https://example.com/linux-x86_64')
        ->and(Platform::findBestMatch($platformUrls, $windowsX32))->toBe('https://example.com/win-32');
});
