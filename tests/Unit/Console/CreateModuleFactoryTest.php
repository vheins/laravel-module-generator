<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:factory (CreateModuleFactory).
 *
 * Arguments: {name} (required), {module} (required)
 * Options: --fillable= (required value) — colon-separated type spec, e.g. title:string,price:decimal
 * Destination: GenerateConfigReader::read('factory') => modules/{Module}/database/factories/{StudlyName}Factory.php
 */
final class CreateModuleFactoryTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'FactoryMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:factory', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:factory'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('model'));
        $this->assertFalse($command->getDefinition()->hasOption('plain'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:factory', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_factory_file_at_generator_path(): void
    {
        $name = 'TestItemFactory';
        $this->artisan('create:module:factory', [
            'name' => $name,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('factory');
        $this->assertModuleFileExists($rel.'/'.$name.'.php', $this->moduleName);
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('class '.$name, $content);
    }

    public function test_factory_with_fillable_renders_typed_attributes(): void
    {
        $name = 'FillableFactory';
        $this->artisan('create:module:factory', [
            'name' => $name,
            'module' => $this->moduleName,
            '--fillable' => 'title:string,price:decimal',
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('factory');
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString("'title'", $content);
        $this->assertStringContainsString("'price'", $content);
    }
}
