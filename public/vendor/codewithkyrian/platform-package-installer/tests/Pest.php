<?php

use Composer\Console\Application;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

const TEST_PROJECT_PATH = __DIR__.'/../test-project';

function setupTestProject(array $platformPackages = []): void
{
    $projectComposerJson = TEST_PROJECT_PATH.'/composer.json';

    $composerData = json_decode(file_get_contents($projectComposerJson), true);

    $composerData['extra']['platform-packages'] = $platformPackages;

    file_put_contents($projectComposerJson, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function runComposerCommandInTestProject(string $command, array $args = []): int
{
    $currentDir = getcwd();
    chdir(TEST_PROJECT_PATH);

    try {
        $fullArgs = array_merge([$command], $args);
        $input = new ArrayInput($fullArgs);
        $output = new BufferedOutput();

        $application = new Application();
        $application->setAutoExit(false);

        return $application->run($input, $output);
    } finally {
        chdir($currentDir);
    }
}
