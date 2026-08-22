<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:migration (CreateModuleMigration).
 */
final class CreateModuleMigrationTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'MigrationMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:migration', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:migration'];
        $this->assertTrue($command->getDefinition()->hasOption('fields'));
        $this->assertTrue($command->getDefinition()->hasOption('plain'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('create'));
        $this->assertFalse($command->getDefinition()->hasOption('table'));
        $this->assertFalse($command->getDefinition()->hasOption('model'));
    }

    public function test_missing_required_arguments_fails(): void
    {
        try {
            $this->artisan('create:module:migration', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_migration_file_at_generator_path(): void
    {
        $basename = 'TestItem';
        $this->artisan('create:module:migration', [
            'basename' => $basename,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $migrationRel = $this->getGeneratorPath('migration');
        $files = glob($this->getModulePath($this->moduleName).'/'.$migrationRel.'/*.php') ?: [];
        $this->assertNotEmpty($files, 'Expected migration file under configured generator path ['.$migrationRel.']');

        $content = file_get_contents((string) end($files));
        $this->assertStringContainsString('Schema', $content);
    }

    public function test_migration_with_fillable_includes_schema(): void
    {
        $basename = 'FillableMigration';
        $this->artisan('create:module:migration', [
            'basename' => $basename,
            'module' => $this->moduleName,
            '--fields=title:string,body:text',
        ])->assertExitCode(0);

        $migrationRel = $this->getGeneratorPath('migration');
        $files = glob($this->getModulePath($this->moduleName).'/'.$migrationRel.'/*.php') ?: [];
        $this->assertNotEmpty($files);
        $content = file_get_contents((string) end($files));
        $this->assertStringContainsString('title', $content);
    }
}
