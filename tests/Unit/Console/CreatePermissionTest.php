<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Tests for create:permission (CreatePermission).
 */
final class CreatePermissionTest extends CommandTestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('create:permission', Artisan::all());
    }

    public function test_command_requires_name_argument(): void
    {
        try {
            $this->artisan('create:permission')->assertFailed();
            $this->fail('Expected missing-argument RuntimeException.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_command_exposes_no_invented_options(): void
    {
        $command = Artisan::all()['create:permission'];
        $this->assertFalse($command->getDefinition()->hasOption('force'));
        $this->assertFalse($command->getDefinition()->hasOption('model'));
        $this->assertFalse($command->getDefinition()->hasOption('count'));
    }
}
