<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Artisan;

/**
 * Tests for create:api:crud (CreateApiCrud).
 */
final class CreateApiCrudTest extends CommandTestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey('create:api:crud', Artisan::all());
    }

    public function test_command_requires_name_argument(): void
    {
        $this->artisan('create:api:crud')->assertFailed();
    }

    public function test_command_exposes_only_action_option(): void
    {
        $command = Artisan::all()['create:api:crud'];
        $this->assertTrue($command->getDefinition()->hasOption('action'));
        $this->assertFalse($command->getDefinition()->hasOption('force'));
    }
}
