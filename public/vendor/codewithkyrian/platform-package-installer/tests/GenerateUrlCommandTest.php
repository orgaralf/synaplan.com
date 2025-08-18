<?php

declare(strict_types=1);

use Codewithkyrian\PlatformPackageInstaller\GenerateUrlCommand;
use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;

beforeEach(function () {
    $application = new Application();
    $urlGeneratorCommand = new GenerateUrlCommand();
    $application->add($urlGeneratorCommand);
    $application->setAutoExit(false);
    $this->command = $application->find('platform:generate-urls');

    $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'platform-package-installer-test-'.uniqid();
    mkdir($this->tempDir);

    $baseComposerJson = [
        'name' => 'test/platform-package',
        'type' => 'library',
        'description' => 'Test Package',
        'version' => '1.0.0',
    ];
    file_put_contents($this->tempDir.DIRECTORY_SEPARATOR.'composer.json', json_encode($baseComposerJson));

    chdir($this->tempDir);
});

afterEach(function () {
    array_map('unlink', glob($this->tempDir.DIRECTORY_SEPARATOR.'*'));
    rmdir($this->tempDir);
});

it('generates platform URLs for GitHub', function () {
    $platformsYaml = [
        'linux-x86_64' => ['exclude' => []],
        'darwin-arm64' => ['exclude' => []],
        'windows-x86_64' => ['exclude' => []],
    ];
    file_put_contents($this->tempDir.'/platforms.yml', Yaml::dump($platformsYaml));

    $input = new ArrayInput([
        'command' => 'platform:generate-urls',
        '--platforms-file' => $this->tempDir.'/platforms.yml',
        '--dist-type' => 'github',
        '--repo-path' => 'vendor/repo',
    ]);

    $output = new BufferedOutput();

    $resultCode = $this->command->run($input, $output);

    expect($resultCode)->toBe(0);

    $composerJson = json_decode(file_get_contents($this->tempDir.'/composer.json'), true);

    expect($composerJson)->toHaveKey('extra')
        ->and($composerJson['extra'])->toHaveKey('platform-urls');

    $platformUrls = $composerJson['extra']['platform-urls'];

    expect($platformUrls)->toHaveCount(3)
        ->and($platformUrls['linux-x86_64'])->toBe('https://github.com/vendor/repo/releases/download/{version}/dist-linux-x86_64.tar.gz')
        ->and($platformUrls['darwin-arm64'])->toBe('https://github.com/vendor/repo/releases/download/{version}/dist-darwin-arm64.tar.gz')
        ->and($platformUrls['windows-x86_64'])->toBe('https://github.com/vendor/repo/releases/download/{version}/dist-windows-x86_64.zip');

});

it('uses the platforms file if not provided', function () {
    $platformsYaml = [
        'linux-x86_64' => ['exclude' => []],
        'darwin-arm64' => ['exclude' => []],
        'windows-x86_64' => ['exclude' => []],
    ];
    file_put_contents($this->tempDir.'/platforms.yml', Yaml::dump($platformsYaml));

    $input = new ArrayInput([
        'command' => 'platform:generate-urls',
        '--dist-type' => 'github',
        '--repo-path' => 'vendor/repo',
    ]);

    $output = new BufferedOutput();

    $resultCode = $this->command->run($input, $output);

    expect($resultCode)->toBe(0);

    $composerJson = json_decode(file_get_contents($this->tempDir.'/composer.json'), true);

    expect($composerJson)->toHaveKey('extra')
        ->and($composerJson['extra'])->toHaveKey('platform-urls');

    $platformUrls = $composerJson['extra']['platform-urls'];

    expect($platformUrls)->toHaveCount(3)
        ->and($platformUrls['linux-x86_64'])->toBe('https://github.com/vendor/repo/releases/download/{version}/dist-linux-x86_64.tar.gz')
        ->and($platformUrls['darwin-arm64'])->toBe('https://github.com/vendor/repo/releases/download/{version}/dist-darwin-arm64.tar.gz')
        ->and($platformUrls['windows-x86_64'])->toBe('https://github.com/vendor/repo/releases/download/{version}/dist-windows-x86_64.zip');


});

it('handles custom URL template', function () {
    $platformsYaml = [
        'linux-x86_64' => ['exclude' => []],
        'darwin-arm64' => ['exclude' => []],
    ];
    file_put_contents($this->tempDir.'/platforms.yml', Yaml::dump($platformsYaml));

    // Prepare command input with custom URL template
    $input = new ArrayInput([
        'command' => 'platform:generate-urls',
        '--platforms-file' => $this->tempDir.'/platforms.yml',
        '--dist-type' => 'https://custom-cdn.com/releases/{version}/dist-{platform}.{ext}',
        '--extension' => 'tar.xz',
    ]);

    $output = new BufferedOutput();

    $resultCode = $this->command->run($input, $output);

    expect($resultCode)->toBe(0);

    $composerJson = json_decode(file_get_contents($this->tempDir.'/composer.json'), true);

    $platformUrls = $composerJson['extra']['platform-urls'];

    expect($platformUrls)->toHaveCount(2)
        ->and($platformUrls['linux-x86_64'])->toBe('https://custom-cdn.com/releases/{version}/dist-linux-x86_64.tar.xz')
        ->and($platformUrls['darwin-arm64'])->toBe('https://custom-cdn.com/releases/{version}/dist-darwin-arm64.tar.xz');
});

it('fails with non-existent platforms file', function () {
    $input = new ArrayInput([
        'command' => 'platform:generate-urls',
        '--platforms-file' => '/path/to/non/existent/platforms.yml',
        '--dist-type' => 'github',
        '--repo-path' => 'vendor/repo',
    ]);

    $output = new BufferedOutput();
    $resultCode = $this->command->run($input, $output);

    expect($resultCode)->toBe(1);
});

it('handles empty platforms file', function () {
    file_put_contents($this->tempDir.'/platforms.yml', '');

    $input = new ArrayInput([
        'command' => 'platform:generate-urls',
        '--platforms-file' => $this->tempDir.'/platforms.yml',
        '--dist-type' => 'github',
        '--repo-path' => 'vendor/repo',
    ]);

    $output = new BufferedOutput();
    $resultCode = $this->command->run($input, $output);

    expect($resultCode)->toBe(0);

    $composerJson = json_decode(file_get_contents($this->tempDir.'/composer.json'), true);

    expect($composerJson['extra']['platform-urls'])->toBeArray()
        ->and($composerJson['extra']['platform-urls'])->toBeEmpty();
});

it('merges with existing extra configuration', function () {
    $platformsYaml = [
        'linux-x86_64' => ['exclude' => []],
    ];
    file_put_contents($this->tempDir.'/platforms.yml', Yaml::dump($platformsYaml));

    $composerJson = json_decode(file_get_contents($this->tempDir.'/composer.json'), true);
    $composerJson['extra'] = [
        'existing-config' => 'test-value'
    ];
    file_put_contents($this->tempDir.'/composer.json', json_encode($composerJson));

    $input = new ArrayInput([
        'command' => 'platform:generate-urls',
        '--platforms-file' => $this->tempDir.'/platforms.yml',
        '--dist-type' => 'github',
        '--repo-path' => 'vendor/repo',
    ]);

    $output = new BufferedOutput();
    $resultCode = $this->command->run($input, $output);

    expect($resultCode)->toBe(0);

    $updatedComposerJson = json_decode(file_get_contents($this->tempDir.'/composer.json'), true);

    expect($updatedComposerJson['extra']['existing-config'])->toBe('test-value')
        ->and($updatedComposerJson['extra']['platform-urls'])->toHaveCount(1)
        ->and($updatedComposerJson['extra']['platform-urls']['linux-x86_64'])->toBe('https://github.com/vendor/repo/releases/download/{version}/dist-linux-x86_64.tar.gz');
});
