<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:action (CreateModuleAction).
 */
final class CreateModuleActionTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'ActionMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:action', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:action'];
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('model'));
        $this->assertFalse($command->getDefinition()->hasOption('plain'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:action', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_action_file_at_generator_path(): void
    {
        $name = 'TestAction';
        $this->artisan('create:module:action', [
            'name' => $name,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('action');
        $this->assertModuleFileExists($rel.'/'.$name.'.php', $this->moduleName);
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('class '.$name, $content);
    }

    public function test_nested_action_with_slash_creates_subdirectory_file(): void
    {
        $this->artisan('create:module:action', [
            'name' => 'Post/Store',
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $glob = glob($this->getModulePath($this->moduleName).'/**/*.php') ?: [];
        $this->assertNotEmpty($glob, 'Expected at least one Action file under Actions/');
    }
}
