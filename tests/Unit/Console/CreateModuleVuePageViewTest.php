<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:vue:page:view (CreateModuleVuePageView).
 */
final class CreateModuleVuePageViewTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'PageViewMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:vue:page:view', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:vue:page:view'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('api'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:vue:page:view', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_view_vue_file_at_configured_path(): void
    {
        $this->artisan('create:module:vue:page:view', [
            'name' => $this->moduleName,
            'module' => $this->moduleName,
            '--fillable=title:string,body:text',
        ])->assertExitCode(0);

        $vuePagesRel = $this->getGeneratorPath('vue-pages');
        $base = $this->getModulePath($this->moduleName).DIRECTORY_SEPARATOR.$vuePagesRel;
        $files = glob($base.'/dashboard/**/[id].vue') ?: glob($base.'/**/[id].vue') ?: [];
        $this->assertNotEmpty($files, 'Expected [id].vue under configured vue-pages path ['.$vuePagesRel.']');
    }
}
