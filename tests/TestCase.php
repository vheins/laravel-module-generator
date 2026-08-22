<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Support\Stub;
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
        $this->app['config']->set('modules.stubs.enabled', true);
        // base_path() inside Orchestra Testbench resolves to the skeleton, not the
        // repo root, so resolve the repo's stubs/modular directory via __DIR__.
        $stubsPath = __DIR__.'/../stubs/modular';
        $this->app['config']->set('modules.stubs.path', $stubsPath);
        Stub::setBasePath($stubsPath);

        // The package's custom generator paths are nested config values. Laravel's
        // mergeConfigFrom does not replace an already-loaded vendor array, so load
        // the package's Vue/factory paths explicitly for the command fixtures.
        $packageConfig = require dirname(__DIR__).'/modules.php';
        $this->app['config']->set('modules.paths.generator', array_replace(
            (array) $this->app['config']->get('modules.paths.generator', []),
            $packageConfig['paths']['generator'],
        ));
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

    /**
     * Create and register a module fixture for commands that resolve modules by name.
     *
     * The package repository (Nwidart\Modules\FileRepository) only discovers modules
     * from module.json manifests discovered by scan(). A bare directory tree is not
     * enough: getModuleName() (ModuleCommandTrait) resolves via findOrFail() and
     * throws ModuleNotFoundException when no manifest exists yet. This helper writes
     * a minimal manifest and rescans the repository so generator commands can
     * resolve the fixture module.
     */
    protected function ensureModuleRegistered(string $moduleName): void
    {
        $modulePath = $this->getModulePath($moduleName);

        if (! is_dir($modulePath)) {
            mkdir($modulePath, 0777, true);
        }

        $manifestPath = $modulePath.DIRECTORY_SEPARATOR.'module.json';
        if (! is_file($manifestPath)) {
            File::put($manifestPath, (string) json_encode([
                'name' => $moduleName,
                'alias' => strtolower($moduleName),
                'description' => '',
                'keywords' => [],
                'priority' => 0,
                'providers' => [],
                'files' => [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $repository = app('modules');
        if (method_exists($repository, 'resetModules')) {
            $repository->resetModules();
        }
        $repository->scan();
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
