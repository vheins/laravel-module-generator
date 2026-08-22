<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module:vue:component:form (CreateModuleVueComponentForm).
 */
final class CreateModuleVueComponentFormTest extends CommandTestCase
{
    protected string $moduleName = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'FormMod'.uniqid();
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
        $this->assertArrayHasKey('create:module:vue:component:form', Artisan::all());
    }

    public function test_command_exposes_only_real_options(): void
    {
        $command = Artisan::all()['create:module:vue:component:form'];
        $this->assertTrue($command->getDefinition()->hasOption('fillable'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('api'));
    }

    public function test_missing_name_fails(): void
    {
        try {
            $this->artisan('create:module:vue:component:form', ['module' => $this->moduleName])->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_creates_form_component_at_configured_path(): void
    {
        $name = 'TestItem';
        $this->artisan('create:module:vue:component:form', [
            'name' => $name,
            'module' => $this->moduleName,
        ])->assertExitCode(0);

        $formRel = $this->getGeneratorPath('vue-components');
        $this->assertModuleFileExists($formRel.'/'.strtolower($name).'-form.vue', $this->moduleName);
    }

    public function test_form_component_with_fillable_renders_inputs(): void
    {
        $name = 'FormWithTitle';
        $this->artisan('create:module:vue:component:form', [
            'name' => $name,
            'module' => $this->moduleName,
            '--fillable=title:string,body:text',
        ])->assertExitCode(0);

        $formRel = $this->getGeneratorPath('vue-components');
        $content = $this->getModuleFileContent($formRel.'/'.strtolower($name).'-form.vue', $this->moduleName);
        $this->assertStringContainsString('input', $content);
    }
}
