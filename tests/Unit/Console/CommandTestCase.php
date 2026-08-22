<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase as BaseTestCase;

/**
 * Base test case for console command testing.
 */
abstract class CommandTestCase extends BaseTestCase
{
    /**
     * Resolve the raw Symfony command instance from the Artisan application.
     *
     * Symfony's Command::__construct() requires an AsCommand attribute when a
     * command is instantiated standalone. The legacy `$signature`/`$name`
     * commands in this package carry no attribute, so constructing them
     * directly throws a LogicException. Resolving them through the Artisan
     * application (which already registered the classes in the command map)
     * lets Symfony's command loader build and configure them properly.
     */
    protected function resolveConsoleCommand(string $commandName): Command
    {
        $kernel = $this->app[KernelContract::class];

        // Testbench's console kernel overrides bootstrappers() with an empty
        // array, so providers are never registered/booted unless a command is
        // actually run. Register and boot providers explicitly so the package
        // service provider's command registration
        // (LaravelModuleGeneratorServiceProvider::configureCommands) runs,
        // then resolve the command from the Artisan application.
        $this->app->registerConfiguredProviders();
        $this->app->loadDeferredProviders();
        $this->app->boot();

        $kernel->bootstrap();

        $artisan = (new \ReflectionClass($kernel))->getMethod('getArtisan');
        $artisan->setAccessible(true);
        $artisan = $artisan->invoke($kernel);

        // Symfony's `has()` resolves the command through the container command
        // loader (ContainerCommandLoader::get()), which builds the command
        // through Laravel's container — the proper path for legacy signature
        // based commands that lack an `AsCommand` attribute.
        if (! $artisan->has($commandName)) {
            $this->fail("Command [{$commandName}] is not registered.");
        }

        return $artisan->get($commandName);
    }

    /**
     * Execute an artisan command with arguments and options.
     */
    protected function executeCommand(string $command, array $arguments = [], array $options = []): int|string
    {
        if (isset($options['--help']) && $options['--help'] === true) {
            return $this->artisan($command, array_merge($arguments, $options));
        }

        return Artisan::call($command, array_merge($arguments, $options));
    }

    /**
     * Get the output of the last executed command.
     */
    protected function getCommandOutput(): string
    {
        return Artisan::output();
    }

    /**
     * Assert that a command succeeded (exit code 0).
     */
    protected function assertCommandSucceeds(int $exitCode): void
    {
        $this->assertEquals(0, $exitCode, 'Command failed with output: '.$this->getCommandOutput());
    }

    /**
     * Assert that a command failed with a specific exit code.
     */
    protected function assertCommandFails(int $exitCode, int $expectedCode = 1): void
    {
        $this->assertEquals($expectedCode, $exitCode, 'Command unexpectedly succeeded with output: '.$this->getCommandOutput());
    }

    /**
     * Assert that command output contains expected string.
     */
    protected function assertCommandOutputContains(int $exitCode, string $expectedOutput): void
    {
        $this->assertStringContainsString($expectedOutput, $this->getCommandOutput(), "Command output did not contain [{$expectedOutput}]. Output: ".$this->getCommandOutput());
    }

    /**
     * Assert that command output does not contain string.
     */
    protected function assertCommandOutputNotContains(int $exitCode, string $unexpectedOutput): void
    {
        $this->assertStringNotContainsString($unexpectedOutput, $this->getCommandOutput(), "Command output unexpectedly contained [{$unexpectedOutput}].");
    }
}
