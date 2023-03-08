<?php

namespace Tests\Commands;

use Smakecloud\Skeema\Commands\SkeemaLintCommand;
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

        $this->artisan('skeema:lint --skip-format')
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
    public function it_prints_github_issue_comment_formatted_output()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        config()->set('skeema.lint.rules', [
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'error',
        ]);

        $this->artisan('skeema:lint --output-format=github')
            ->expectsOutputToContain('::error file=' . $this->getSkeemaDir() . '/' . 'test1.sql' . ',line=')
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

    /** @test */
    public function it_uses_the_right_args()
    {
        $args = $this->getSkeemaArgs(
            SkeemaLintCommand::class,
            '--skip-format'
            . ' --update-views'
            . ' --strip-partitioning'
            . ' --strip-definer="test4"'
            . ' --allow-auto-inc="test5"'
            . ' --allow-charset="test6"'
            . ' --allow-compression="test7"'
            . ' --allow-definer="test8"'
            . ' --allow-engine="test9"'
            . ' --output-format="default"'
        );

        $this->assertArrayHasKey('skip-format', $args);
        $this->assertEquals(true, $args['skip-format']);

        $this->assertArrayHasKey('update-views', $args);
        $this->assertEquals(true, $args['update-views']);

        $this->assertArrayHasKey('strip-definer', $args);
        $this->assertEquals('test4', $args['strip-definer']);

        $this->assertArrayHasKey('strip-partitioning', $args);
        $this->assertEquals(true, $args['strip-partitioning']);

        $this->assertArrayHasKey('allow-auto-inc', $args);
        $this->assertEquals('test5', $args['allow-auto-inc']);

        $this->assertArrayHasKey('allow-charset', $args);
        $this->assertEquals('test6', $args['allow-charset']);

        $this->assertArrayHasKey('allow-compression', $args);
        $this->assertEquals('test7', $args['allow-compression']);

        $this->assertArrayHasKey('allow-definer', $args);
        $this->assertEquals('test8', $args['allow-definer']);

        $this->assertArrayHasKey('allow-engine', $args);
        $this->assertEquals('test9', $args['allow-engine']);

        /** Not an option of skeema */
        $this->assertArrayNotHasKey('output-format', $args);
    }
}
