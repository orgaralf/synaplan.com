# Composer Platform Package Installer

The Composer Platform Package Installer is a powerful Composer plugin that provides fine-grained control over package
distribution across different platforms and architectures. Unlike traditional Composer package management, this plugin
allows you to:

- Exclude specific folders and files for different platforms
- Generate platform-specific distribution URLs
- Customize package installation for various operating systems and architectures

## Requirements

- PHP 8.1 or higher
- Composer 2.0 or higher

## Installation

Install the plugin using Composer:

```bash
composer require codewithkyrian/platform-package-installer
```

## Usage Guide

### Step 1: Package Configuration

Change your package type to `platform-package` in `composer.json`:

```json
{
  "name": "org/library",
  "type": "platform-package",
  "require": {
    "codewithkyrian/platform-package-installer": "^1.0"
  }
}
```

### Step 2: Create Platforms Configuration

Create a `platforms.yml` file in the root of your project and list the platforms you want to support:

```yaml
linux-x86_64:
linux-arm64:
darwin-arm64:
darwin-x86_64:
windows-32:
windows-64:
```

You can also specify exclusion rules for each platform (this will come in handy later when generating distribution
archives).

```yaml
linux-x86_64:
  exclude:
    - libs/linux-arm64
    - libs/darwin-*
    - libs/windows-*
    - "*.exe"
    - "*.dylib"
    - "**/*.windows.*"

darwin-x86_64:
  exclude:
    - libs/darwin-arm64
    - libs/windows-*
    - libs/linux-*
    - "*.exe"
    - "*.so"
    - "**/*.linux.*"

# Add configurations for other platforms similarly
```

**Exclusion Rule Patterns:**

- Use standard glob patterns to specify exclusions
- `*` matches any number of characters
- `**` matches nested directories
- Platform-specific libraries, binaries, and incompatible files can be easily filtered

### Step 3: Generate Distribution URLs

Use the `platform:generate-urls` command to create platform-specific download URLs and add them to `composer.json`. This
is very important because Composer will use these URLs to download the appropriate platform-specific package.

```bash
composer platform:generate-urls --dist-type=github --repo-path=vendor/repo

// Or using a custom template

composer platform:generate-urls --dist-type=https://cdn.example.com/vendor/repo/release-{version}-{platform}.{ext}
```

**Command Options**

- `--platforms-file`: Path to the platforms.yml file (optional, defaults to `platforms.yml`)
- `--dist-type`: Distribution source type (`github`, `gitlab`, `huggingface`, or a custom URL template)
- `--repo-path`: Repository path (optional when using a custom URL template)
- `--extension`: File extension (optional. Uses .zip for Windows and .tar.gz for others)

For the example above, the generated config added to `composer.json` would look like:

```json
{
  "extra": {
    "platform-urls": {
      "linux-x86_64": "https://github.com/Codewithkyrian/example/releases/download/{version}/dist-linux-x86_64.tar.gz",
      "darwin-arm64": "https://github.com/Codewithkyrian/example/releases/download/{version}/dist-darwin-arm64.tar.gz"
    }
  }
}
```

Now the next time someone installs your package, Composer will use the generated URLs to download the appropriate
platform-specific package. The good thing is that if for any reason the URLs are wrong or not working, Composer
falls back to downloading the original source (which means more things to download and slower installation but hey, it's
better than nothing).

## Platform Identifiers

Platform Identifiers are used to specify the platform-specific package URL. Platform identifiers can be:

- **Base platform names**: The supported base platform names are `linux`, `darwin`, `windows` and `raspberrypi`. This
  means that the distribution archive can be used on any architecture of these platforms.
- **Specific architectures**: You can be more specific by specifying the architecture as well. The identifier is formed
  by joining the platform identifier with the architecture identifier (`-`), e.g. `darwin-arm64`, `darwin-x86_64`,
  `windows-32`, `windows-64`, `linux-aarch64`, etc.
- **Universal**: Use `all` to cover every platform

## Distribution Archives

For regular packages, GitHub and GitLab provide an automatic distribution archive (tarball or zip) for each release.
Using this package however, means you can no longer rely on those automatic distribution archives.

The `composer platform:generate-urls` only generates urls for the platforms specified in the `platforms.yml` file using a
template, but you still need to manually generate the archive for each platform and make them available at those
locations.

### GitHub

The generated GitHub distribution archive is available at the following URL:

```
https://github.com/vendor/package/releases/download/{version}/dist-{platform}.{ext}
```

`{platform}` and `{ext}` will be replaced with the platform identifier and the file extension when generating the URL
while `{version}` will be replaced with the package version at installation time.

The package provides a convenient example [GitHub Actions workflow](workflows/github-release.yml) that uses the
`platforms.yml` file to generate the distribution archives every release and upload them as release assets. Feel free to
modify the workflow to suit your needs.

### GitLab

The GitLab distribution archive is available at the following URL:

```
https://gitlab.com/vendor/package/-/releases/{version}/downloads/dist-{platform}.{ext}
```

At the moment, there's no example workflow for GitLab. Contributions are welcome for a similar workflow.

> Always test the workflow thoroughly to ensure it generates the correct distribution archives for each platform.

## Runtime Utility

The package provides a powerful utility method `findBestMatch()` to easily handle platform-specific resources at
runtime. It finds the most appropriate match for the current platform from a given set of platform-specific entries.

```php
use Codewithkyrian\PlatformPackageInstaller\Platform;

// Example with directory paths
$libraryPaths = [
    'linux-x86_64' => '/path/to/linux/x86_64/libs',
    'darwin-arm64' => '/path/to/mac/arm64/libs',
    'windows-x86_64' => '/path/to/windows/x86_64/libs'
];

$bestLibraryPath = Platform::findBestMatch($libraryPaths);

// Example with configuration arrays
$platformConfigs = [
    'linux-x86_64' => [
        'library_path' => '/path/to/linux/libs',
        'additional_config' => ['key' => 'value']
    ],
    'darwin-arm64' => [
        'library_path' => '/path/to/mac/libs',
        'additional_config' => ['key' => 'another_value']
    ]
];

$currentPlatformConfig = Platform::findBestMatch($platformConfigs);
```

## Tests

The `tests` folder contains a suite of tests that verify the behavior of the plugin. To run the tests, you need
to install the development dependencies using the `composer install --dev` command. Then, run the tests using either
the `composer test` command or the `./vendor/bin/pest` command.

## License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/codewithkyrian/platform-package-installer/blob/main/LICENSE) file for more information.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
