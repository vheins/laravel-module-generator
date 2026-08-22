<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:model (CreateModuleModel).
 */
final class CreateModuleModelTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'ModelMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:model', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:model'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertTrue($command->getDefinition()->hasOption('migration'));
        $this->assertTrue($command->getDefinition()->hasOption('controller'));
        $this->assertTrue($command->getDefinition()->hasOption('seed'));
        $this->assertTrue($command->getDefinition()->hasOption('request'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('fields'));
    }

    public function test_missing_required_model_argument_fails(): void
    {
        try {
            $this->artisan('create:module:model', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_model_with_request_flag_creates_request(): void
    {
        $name = 'PostWithRequest';
        $this->artisan('create:module:model', [
            'model' => $name,
            'module' => $this->moduleName,
            '--request' => true,
            '--fillable' => 'title:string,body:text',
        ])->assertExitCode(0);

        $modelRel = $this->getGeneratorPath('model');
        $this->assertModuleFileExists($modelRel.'/'.$name.'.php', $this->moduleName);

        $requestRel = $this->getGeneratorPath('request');
        $this->assertModuleFileExists($requestRel.'/'.$name.'Request.php', $this->moduleName);
    }

    public function test_model_with_fillable_generates_rules(): void
    {
        $name = 'FillableItem';
        $this->artisan('create:module:model', [
            'model' => $name,
            'module' => $this->moduleName,
            '--fillable' => 'title:string,body:text',
        ])->assertExitCode(0);

        $modelRel = $this->getGeneratorPath('model');
        $content = $this->getModuleFileContent($modelRel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('title', $content);
    }

    public function test_model_file_uses_correct_namespace(): void
    {
        $name = 'NsItem';
        $this->artisan('create:module:model', [
            'model' => $name,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $modelRel = $this->getGeneratorPath('model');
        $content = $this->getModuleFileContent($modelRel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString($this->moduleName, $content);
    }
}
