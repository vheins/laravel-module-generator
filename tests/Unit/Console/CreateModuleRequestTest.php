<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:request (CreateModuleRequest).
 */
final class CreateModuleRequestTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'ReqMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:request', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:request'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('rules'));
        $this->assertFalse($command->getDefinition()->hasOption('messages'));
    }

    public function test_missing_name_argument_fails(): void
    {
        try {
            $this->artisan('create:module:request', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_request_file_at_generator_path(): void
    {
        $name = 'StoreItemRequest';
        $this->artisan('create:module:request', [
            'name' => $name,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('request');
        $this->assertModuleFileExists($rel.'/'.$name.'.php', $this->moduleName);
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('class '.$name, $content);
    }

    public function test_request_with_fillable_includes_validation_rules(): void
    {
        $name = 'CreateItemRequest';
        $this->artisan('create:module:request', [
            'name' => $name,
            'module' => $this->moduleName,
            '--fillable' => 'title:string,category_id:foreignId',
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('request');
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('title', $content);
        $this->assertStringContainsString('required', $content);
    }

    public function test_request_with_fillable_handles_foreign_key(): void
    {
        $reqName = 'ForeignReq'.uniqid().'Request';
        $this->artisan('create:module:request', [
            'name' => $reqName,
            'module' => $this->moduleName,
            '--fillable' => 'author_id:foreignUuid,name:string',
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('request');
        $content = $this->getModuleFileContent($rel.'/'.$reqName.'.php', $this->moduleName);
        $this->assertStringContainsString('author', $content);
    }
}
