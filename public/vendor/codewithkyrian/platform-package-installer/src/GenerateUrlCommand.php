<?php

declare(strict_types=1);

namespace Codewithkyrian\PlatformPackageInstaller;

use Composer\Command\BaseCommand;
use Composer\Console\Input\InputOption;
use Composer\Factory;
use Composer\Json\JsonFile;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GenerateUrlCommand extends BaseCommand
{
    private const DIST_TYPES = [
        'github' => 'https://github.com/{repo_path}/releases/download/{version}/dist-{platform}.{ext}',
        'gitlab' => 'https://gitlab.com/{repo_path}/-/releases/{version}/downloads/dist-{platform}.{ext}',
        'huggingface' => 'https://huggingface.co/{repo_path}/resolve/{version}/dist-{platform}.{ext}',
    ];

    protected function configure(): void
    {
        $this->setName('platform:generate-urls')
            ->setDescription('Generate platform-specific URLs from a platforms.yml file')
            ->addOption(
                'dist-type',
                'dist',
                InputOption::VALUE_REQUIRED,
                'Distribution type (github, gitlab, huggingface, or custom URL template)'
            )
            ->addOption(
                'platforms-file',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Path to platforms.yml file'
            )
            ->addOption(
                'repo-path',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Repository path (vendor/repo-name, optional when using a custom URL template)'
            )
            ->addOption(
                'extension',
                'e',
                InputOption::VALUE_OPTIONAL,
                'File extension (optional, will be auto-determined)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $platformsFile = $input->getOption('platforms-file');
        $distType = $input->getOption('dist-type');
        $repoPath = $input->getOption('repo-path');
        $extensionOverride = $input->getOption('extension');

        $platformsFile ??= dirname(Factory::getComposerFile()). DIRECTORY_SEPARATOR.'platforms.yml';

        if (!file_exists($platformsFile)) {
            $output->writeln("<error>Platforms file not found: $platformsFile</error>");
            return self::FAILURE;
        }

        $platformsData = Yaml::parseFile($platformsFile);
        $urlTemplate = $this->resolveUrlTemplate($distType, $repoPath);
        $platformUrls = $this->generatePlatformUrls($platformsData, $urlTemplate, $extensionOverride);

        $this->updateComposerJson($platformUrls, $output);

        $output->writeln("<info>Platform URLs generated successfully!</info>");
        foreach ($platformUrls as $platform => $url) {
            $output->writeln("  - $platform: $url");
        }
        return self::SUCCESS;
    }

    private function resolveUrlTemplate(string $distType, ?string $repoPath = null): string
    {
        if (isset(self::DIST_TYPES[$distType])) {
            $template = self::DIST_TYPES[$distType];
            return str_replace('{repo_path}', $repoPath, $template);
        }

        return $distType;
    }

    private function generatePlatformUrls(
        ?array  $platformsData,
        string  $urlTemplate,
        ?string $extensionOverride = null
    ): array
    {
        $platformUrls = [];
        $platformsData ??= [];

        foreach ($platformsData as $platform => $platformConfig) {
            $ext = $extensionOverride ?? (str_starts_with(strtolower($platform), 'windows') ? 'zip' : 'tar.gz');
            $ext = ltrim($ext, '.');

            $url = str_replace(['{platform}', '{ext}'], [$platform, $ext], $urlTemplate);

            $platformUrls[$platform] = $url;
        }

        return $platformUrls;
    }

    private function updateComposerJson(array $platformUrls, OutputInterface $output): void
    {
        $composerJsonPath = Factory::getComposerFile();
        $jsonFile = new JsonFile($composerJsonPath);
        $composerJson = $jsonFile->read();

        $composerJson['extra'] = $composerJson['extra'] ?? [];
        $composerJson['extra']['platform-urls'] = $platformUrls;

        $jsonFile->write($composerJson);

        $output->writeln("<info>Updated composer.json with platform URLs</info>");
    }
}
