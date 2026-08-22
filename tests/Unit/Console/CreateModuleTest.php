<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:module (CreateModule).
 */
final class CreateModuleTest extends CommandTestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('create:module', Artisan::all());
    }

    public function test_command_exposes_only_blueprint_option(): void
    {
        $command = Artisan::all()['create:module'];
        $this->assertTrue($command->getDefinition()->hasOption('blueprint'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('api'));
        $this->assertFalse($command->getDefinition()->hasOption('vue'));
    }

    public function test_missing_blueprint_fails(): void
    {
        try {
            $this->artisan('create:module')->assertFailed();
            $this->fail('Expected missing-option RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_invalid_blueprint_path_fails_gracefully(): void
    {
        $this->artisan('create:module', ['--blueprint' => 'missing-blueprint-t03.yaml'])->assertFailed();
    }
}
