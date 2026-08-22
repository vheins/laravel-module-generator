<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:controller (CreateModuleController).
 */
final class CreateModuleControllerTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'CtrlMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:controller', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:controller'];
        $this->assertTrue($command->getDefinition()->hasOption('plain'));
        $this->assertTrue($command->getDefinition()->hasOption('api'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('vue'));
    }

    public function test_missing_required_controller_argument_fails(): void
    {
        try {
            $this->artisan('create:module:controller', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_controller_file_at_generator_path(): void
    {
        $name = 'TestItemController';
        $this->artisan('create:module:controller', [
            'controller' => $name,
            'module' => $this->moduleName,
            '--api' => true,
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('controller');
        $this->assertModuleFileExists($rel.'/'.$name.'.php', $this->moduleName);
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('class '.$name, $content);
    }
}
