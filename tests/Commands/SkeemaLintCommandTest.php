<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaLintCommandTest extends TestCase
{
    /** @test */
    public function it_exits_with_the_correct_error_code_if_skeema_config_could_not_be_found()
    {
        $this->artisan('skeema:lint')
            ->expectsOutputToContain('Skeema config file not found at')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException(''))->getExitCode()
            );
    }

    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->artisan('skeema:lint')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_success_if_charset_rule_is_set_to_ignore()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        config()->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'ignore',
        ]);

        $this->artisan('skeema:lint')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_charset_rule_is_set_to_warning()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        config()->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'warning',
        ]);

        $this->artisan('skeema:lint')
            ->expectsOutputToContain('Skeema linter exited with warnings. See output above for details.')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithWarningsException())->getExitCode()
            );
    }

    /** @test */
    public function it_exits_with_success_if_charset_rule_is_set_to_warning_and_ignore_warnings_option_is_set()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        config()->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'warning',
        ]);

        $this->artisan('skeema:lint', ['--ignore-warnings' => true])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_charset_rule_is_set_to_error()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        config()->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'error',
        ]);

        $this->artisan('skeema:lint')
            ->expectsOutputToContain('Skeema linter exited with errors. See output above for details.')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithErrorsException())->getExitCode()
            );
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_charset_rule_is_set_to_error_and_ignore_warnings_option_is_set()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        config()->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'error',
        ]);

        $this->artisan('skeema:lint', ['--ignore-warnings' => true])
            ->expectsOutputToContain('Skeema linter exited with errors. See output above for details.')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithErrorsException())->getExitCode()
            );
    }
}
