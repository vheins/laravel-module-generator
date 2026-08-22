<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:vue:component:link (CreateModuleVueComponentLink).
 */
final class CreateModuleVueComponentLinkTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'LinkMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:vue:component:link', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:vue:component:link'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('api'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:vue:component:link', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_dashboard_link_at_configured_path(): void
    {
        $this->artisan('create:module:vue:component:link', [
            'name' => 'Alpha',
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $linkRel = $this->getGeneratorPath('vue-components');
        $files = glob($this->getModulePath($this->moduleName).DIRECTORY_SEPARATOR.$linkRel.DIRECTORY_SEPARATOR.'*-dashboard-link.vue') ?: [];
        $this->assertNotEmpty($files);
    }

    public function test_link_filename_ignores_name_and_uses_module(): void
    {
        $this->artisan('create:module:vue:component:link', ['name' => 'Alpha', 'module' => $this->moduleName])->assertExitCode(0);
        $this->artisan('create:module:vue:component:link', ['name' => 'Beta', 'module' => $this->moduleName, '--fillable' => 'id:string'])->assertExitCode(0);

        $files = glob($this->getModulePath($this->moduleName).DIRECTORY_SEPARATOR.$this->getGeneratorPath('vue-components').DIRECTORY_SEPARATOR.'*-dashboard-link.vue') ?: [];
        // Should only create one file (based on module name), not two.
        $this->assertEquals(1, count($files));
    }
}
