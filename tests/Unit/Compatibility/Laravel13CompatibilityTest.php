<?php

declare(strict_types=1);

namespace Tests\Unit\Compatibility;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase as PackageTestCase;
use Vheins\LaravelModuleGenerator\Providers\LaravelModuleGeneratorServiceProvider;

/**
 * Scoped compatibility contract tests for Laravel 13 support.
 *
 * These tests verify the *compatibility contract* (composer metadata +
 * provider/command/stub behavior) without re-running the full command
 * generation suite (covered by Tests\Unit\Console\*).
 *
 * @group compatibility
 */
final class Laravel13CompatibilityTest extends PackageTestCase
{
    /**
     * Assert a version constraint allows a concrete version.
     */
    private function assertConstraintAllows(string $constraint, string $version, string $message = ''): void
    {
        $parser = new VersionParser;

        $this->assertTrue(
            Semver::satisfies($parser->normalize($version), $constraint),
            $message ?: sprintf('Constraint [%s] does not allow version [%s].', $constraint, $version)
        );
    }

    /**
     * Read the package metadata under test.
     *
     * @return array{require: array<string, string>, require-dev: array<string, string>}
     */
    private function composerMetadata(): array
    {
        /** @var array{require: array<string, string>, require-dev: array<string, string>} $metadata */
        $metadata = json_decode(
            (string) file_get_contents(__DIR__.'/../../../composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return $metadata;
    }

    /**
     * Read a required package constraint from composer.json.
     */
    private function composerConstraint(string $section, string $package): string
    {
        $metadata = $this->composerMetadata();

        $this->assertArrayHasKey($section, $metadata);
        $this->assertArrayHasKey($package, $metadata[$section]);

        return $metadata[$section][$package];
    }

    /**
     * Positive: laravel/framework constraint admits Laravel 13.
     */
    public function test_framework_constraint_allows_laravel_13(): void
    {
        $this->assertConstraintAllows($this->composerConstraint('require', 'laravel/framework'), '13.26.1');
    }

    /**
     * Positive: laravel/framework constraint still admits Laravel 11 and 12.
     */
    public function test_framework_constraint_keeps_prior_major_support(): void
    {
        $constraint = $this->composerConstraint('require', 'laravel/framework');

        $this->assertConstraintAllows($constraint, '11.49.0');
        $this->assertConstraintAllows($constraint, '12.67.0');
    }

    /**
     * Negative: the package must not silently advertise Laravel 14 support.
     */
    public function test_framework_constraint_rejects_laravel_14(): void
    {
        $parser = new VersionParser;

        $this->assertFalse(
            Semver::satisfies($parser->normalize('14.0.0'), $this->composerConstraint('require', 'laravel/framework'))
        );
    }

    /**
     * Positive: PHP constraint admits the PHP versions Laravel 13 supports
     * (Laravel 13 requires PHP ^8.3; 8.4 is the runtime of the CI matrix).
     */
    public function test_php_constraint_allows_laravel_13_runtimes(): void
    {
        $constraint = $this->composerConstraint('require', 'php');

        $this->assertConstraintAllows($constraint, '8.3.0');
        $this->assertConstraintAllows($constraint, '8.4.24');
    }

    /**
     * Negative: the package rejects unsupported PHP versions.
     *
     * Composer caret semantics (`^8.2`) admit any 8.x release, so only
     * versions below the floor must be rejected — a future 8.5 runtime is
     * intentionally allowed (forward compatibility, matching Composer's own
     * resolution of `^8.2 || ^8.3 || ^8.4`).
     */
    public function test_php_constraint_rejects_unsupported_versions(): void
    {
        $parser = new VersionParser;
        $constraint = $this->composerConstraint('require', 'php');

        $this->assertFalse(Semver::satisfies($parser->normalize('8.1.0'), $constraint));
        $this->assertFalse(Semver::satisfies($parser->normalize('7.4.33'), $constraint));
    }

    /**
     * Positive: the current runtime (PHP 8.4) satisfies the package's PHP constraint.
     */
    public function test_current_runtime_is_supported(): void
    {
        $this->assertConstraintAllows($this->composerConstraint('require', 'php'), PHP_VERSION);
    }

    /**
     * Positive: the testbench requirement allows the Laravel 13 series.
     */
    public function test_testbench_constraint_allows_laravel_13(): void
    {
        $this->assertConstraintAllows($this->composerConstraint('require-dev', 'orchestra/testbench'), '11.2.0');
    }

    /**
     * Negative: the testbench requirement must not be pinned to a series that
     * predates Laravel 13.
     */
    public function test_testbench_constraint_rejects_series_predating_laravel_13(): void
    {
        $parser = new VersionParser;

        $this->assertFalse(
            Semver::satisfies($parser->normalize('8.0.0'), $this->composerConstraint('require-dev', 'orchestra/testbench'))
        );
    }

    /**
     * Positive: the symfony/yaml requirement admits the Symfony versions that
     * ship with Laravel 13 (symfony/yaml ^7.4|^8.0).
     */
    public function test_symfony_yaml_constraint_allows_laravel_13_stack(): void
    {
        $constraint = $this->composerConstraint('require', 'symfony/yaml');

        $this->assertConstraintAllows($constraint, '7.4.15');
        $this->assertConstraintAllows($constraint, '8.0.15');
    }

    /**
     * Positive: the service provider is registered by the testbench application.
     */
    public function test_service_provider_registers(): void
    {
        $app = $this->app;
        $this->assertInstanceOf(ApplicationContract::class, $app);

        $provider = $app->getProvider(LaravelModuleGeneratorServiceProvider::class);

        $this->assertInstanceOf(
            LaravelModuleGeneratorServiceProvider::class,
            $provider,
            'LaravelModuleGeneratorServiceProvider is not registered in the application.'
        );
    }

    /**
     * Positive: key generator commands are registered on the Artisan application.
     */
    public function test_key_commands_are_registered(): void
    {
        $commands = [
            'create:module',
            'create:module:migration',
            'create:module:factory',
            'create:module:model',
            'create:module:request',
            'create:module:seeder',
            'create:permission',
            'create:api:crud',
        ];

        $registered = array_keys(Artisan::all());

        foreach ($commands as $command) {
            $this->assertContains($command, $registered, "Artisan command [{$command}] is not registered.");
        }
    }

    /**
     * Positive: registered commands resolve to concrete, executable command classes.
     */
    public function test_key_commands_resolve_to_console_commands(): void
    {
        $commands = [
            'create:module',
            'create:module:migration',
            'create:module:factory',
        ];

        foreach ($commands as $command) {
            $resolved = Artisan::all()[$command];
            $this->assertInstanceOf(Command::class, $resolved, "Command [{$command}] does not resolve to a Console Command.");
            $this->assertNotSame('', $resolved->getName(), "Command [{$command}] resolved without a name.");
        }
    }

    /**
     * Negative: the provider must not register a command class that does not exist.
     */
    public function test_provider_command_classes_exist(): void
    {
        foreach (LaravelModuleGeneratorServiceProvider::COMMANDS as $class) {
            $this->assertTrue(class_exists($class), "Command class [{$class}] does not exist.");
        }
    }

    /**
     * Positive: every command class referenced by the service provider is registered
     * on the Artisan application (parity guard).
     */
    public function test_provider_registered_commands_are_registered_on_artisan(): void
    {
        $registered = array_keys(Artisan::all());

        foreach (LaravelModuleGeneratorServiceProvider::COMMANDS as $class) {
            $instance = new $class;

            $this->assertContains(
                $instance->getName(),
                $registered,
                "Command class [{$class}] is not registered on Artisan."
            );
        }
    }

    /**
     * Negative: factory stubs must not use APIs removed/deprecated in Laravel 13.
     *
     * The stubs themselves are faker-free, but the factory generator command
     * renders `$this->faker->...` attribute values when --fillable is provided.
     * Laravel 13 removed the deprecated Factory::$faker property, so the
     * generated code must not call `faker()` on the factory instance.
     */
    public function test_factory_generation_avoids_removed_faker_api(): void
    {
        $stub = (string) file_get_contents(__DIR__.'/../../../stubs/factory.stub');

        $this->assertStringContainsString('extends Factory', $stub);
        $this->assertStringNotContainsString('newFactory', $stub);
        $this->assertStringNotContainsString('$this->faker', $stub, 'Factory stubs must not call the removed Factory::$faker property.');
    }

    /**
     * Negative: migration stubs must use the anonymous-class migration introduced
     * in Laravel 9 and still required in Laravel 13.
     */
    public function test_migration_stub_uses_anonymous_class_syntax(): void
    {
        $stub = (string) file_get_contents(__DIR__.'/../../../stubs/migration.stub');

        $this->assertStringContainsString('return new class extends Migration', $stub);
        $this->assertStringNotContainsString('Schema::dropIfExists', $stub);
    }

    /**
     * Positive: migration stubs rely on Blueprint column methods that are still
     * present in the installed Laravel 13 framework.
     */
    public function test_migration_stub_uses_current_blueprint_api(): void
    {
        $blueprint = new \ReflectionClass(Blueprint::class);

        foreach (['datetimes', 'softDeletesDatetime', 'foreignUuid', 'uuid'] as $method) {
            $this->assertTrue(
                $blueprint->hasMethod($method),
                "Blueprint::{$method}() is missing from the installed framework — stub would break."
            );
        }
    }
}
