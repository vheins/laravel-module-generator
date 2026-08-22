<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Vheins\LaravelModuleGenerator\Providers\LaravelModuleGeneratorServiceProvider;

/**
 * Base TestCase for Laravel Module Generator.
 */
abstract class TestCase extends BaseTestCase
{
    protected readonly string $modulesBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create modules directory if it doesn't exist
        $basePath = base_path('modules');
        if (! is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        $this->modulesBasePath = $basePath;

        // Merge config for testing
        $this->app['config']->set('modules.paths.modules', $basePath);
        $this->app['config']->set('modules.namespace', 'Vheins');
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelModulesServiceProvider::class,
            LaravelModuleGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('modules.paths.modules', base_path('modules'));
        $app['config']->set('modules.namespace', 'Vheins');
        $app['config']->set('modules.scan.enabled', false);
    }

    protected function getModulePath(string $moduleName): string
    {
        return base_path('modules').DIRECTORY_SEPARATOR.$moduleName;
    }

    protected function cleanModule(string $moduleName): void
    {
        $path = $this->getModulePath($moduleName);
        if (is_dir($path)) {
            File::deleteDirectory($path);
        }
    }

    protected function assertModuleExists(string $moduleName): void
    {
        $this->assertDirectoryExists($this->getModulePath($moduleName));
    }

    protected function getModuleFilePath(string $relativePath, string $moduleName): string
    {
        return $this->getModulePath($moduleName).DIRECTORY_SEPARATOR.ltrim($relativePath, DIRECTORY_SEPARATOR);
    }

    protected function assertModuleFileExists(string $relativePath, string $moduleName): void
    {
        $this->assertFileExists($this->getModuleFilePath($relativePath, $moduleName));
    }

    protected function getModuleFileContent(string $relativePath, string $moduleName): string
    {
        return (string) file_get_contents($this->getModuleFilePath($relativePath, $moduleName));
    }

    protected function getGeneratorPath(string $generator): string
    {
        return trim((string) config("modules.paths.generator.{$generator}.path"), DIRECTORY_SEPARATOR);
    }
}
