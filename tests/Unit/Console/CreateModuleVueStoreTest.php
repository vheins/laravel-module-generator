<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:vue:store (CreateModuleVueStore).
 */
final class CreateModuleVueStoreTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'StoreMod'.uniqid();
        $modulePath = $this->getModulePath($this->moduleName);
        if (! is_dir($modulePath)) {
            mkdir($modulePath, 0777, true);
        }
        $subDirs = ['Models', 'Controllers', 'Requests', 'Actions', 'Migrations', 'database/factories', 'Vue/store', 'Vue/components', 'Vue/pages', 'seeders'];
        foreach ($subDirs as $dir) {
            mkdir($modulePath.DIRECTORY_SEPARATOR.$dir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->cleanModule($this->moduleName);
        parent::tearDown();
    }

    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('create:module:vue:store', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:vue:store'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('api'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:vue:store', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_store_file_at_vue_stores_path(): void
    {
        $name = 'TestItem';
        $this->artisan('create:module:vue:store', [
            'name' => $name,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $storeRel = $this->getGeneratorPath('vue-stores');
        $this->assertModuleFileExists($storeRel.'/'.strtolower($name).'.js', $this->moduleName);
    }

    public function test_store_path_uses_vue_stores_not_vue_components(): void
    {
        $this->artisan('create:module:vue:store', [
            'name' => 'Item',
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $stores = glob($this->getModulePath($this->moduleName).'/'.$this->getGeneratorPath('vue-stores').'/*.js') ?: [];
        $components = glob($this->getModulePath($this->moduleName).'/'.$this->getGeneratorPath('vue-components').'/*.vue') ?: [];

        $this->assertNotEmpty($stores);
        $this->assertEmpty($components);
    }
}
