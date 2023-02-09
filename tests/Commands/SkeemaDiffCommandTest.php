<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaDiffCommandTest extends TestCase
{
    /** @test */
    public function it_exits_with_the_correct_error_code_if_skeema_config_could_not_be_found()
    {
        $this->artisan('skeema:diff')
            ->expectsOutputToContain('Skeema config file not found at')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException(''))->getExitCode()
            );
    }

    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->artisan('skeema:diff')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_changes_were_found()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-new-column-sql')
        );

        $this->artisan('skeema:diff')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithWarningsException())->getExitCode()
            );
    }

    /** @test */
    public function is_exits_with_success_if_changes_were_found_but_the_ignore_warnings_flag_is_set()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-new-column-sql')
        );

        $this->artisan('skeema:diff', ['--ignore-warnings' => true])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_errors_were_found()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-invalid-charset-sql')
        );

        $this->artisan('skeema:diff')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithErrorsException())->getExitCode()
            );
    }
}
