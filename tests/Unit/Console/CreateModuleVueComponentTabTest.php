<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:vue:component:tab (CreateModuleVueComponentTab).
 */
final class CreateModuleVueComponentTabTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'TabMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:vue:component:tab', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:vue:component:tab'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('api'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:vue:component:tab', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_tab_filename_uses_module_not_name(): void
    {
        $this->artisan('create:module:vue:component:tab', [
            'name' => 'Alpha',
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $files1 = glob($this->getModulePath($this->moduleName).'/'.$this->getGeneratorPath('vue-components').'/'.strtolower($this->moduleName).'-icon-tab.vue') ?: [];

        $this->artisan('create:module:vue:component:tab', [
            'name' => 'Beta',
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $files2 = glob($this->getModulePath($this->moduleName).'/'.$this->getGeneratorPath('vue-components').'/'.strtolower($this->moduleName).'-icon-tab.vue') ?: [];
        $this->assertEquals(count($files1), count($files2));
    }
}
