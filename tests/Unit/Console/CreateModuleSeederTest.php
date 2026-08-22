<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:seeder (CreateModuleSeeder).
 */
final class CreateModuleSeederTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'SeederMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:seeder', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:seeder'];
        $this->assertTrue($command->getDefinition()->hasOption('master'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('model'));
        $this->assertFalse($command->getDefinition()->hasOption('count'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:seeder', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_seeder_file_at_generator_path(): void
    {
        $name = 'PostSeeder';
        $this->artisan('create:module:seeder', [
            'name' => $name,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('seeder');
        $this->assertModuleFileExists($rel.'/'.$name.'.php', $this->moduleName);
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('class '.$name, $content);
    }

    public function test_master_with_already_suffixed_name(): void
    {
        $name = 'PostDatabaseSeeder';
        $this->artisan('create:module:seeder', [
            'name' => $name,
            'module' => $this->moduleName,
            '--master' => true,
        ])->assertExitCode(0);

        $rel = $this->getGeneratorPath('seeder');
        $this->assertModuleFileExists($rel.'/'.$name.'.php', $this->moduleName);
        $content = $this->getModuleFileContent($rel.'/'.$name.'.php', $this->moduleName);
        $this->assertStringContainsString('class '.$name, $content);
    }
}
