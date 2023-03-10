<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaPushCommandTest extends TestCase
{
    /** @test */
    public function it_exits_with_the_correct_error_code_if_skeema_config_could_not_be_found()
    {
        $this->artisan('skeema:push')
            ->expectsOutputToContain('Skeema config file not found at')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException(''))->getExitCode()
            );
    }

    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->artisan('skeema:push')
            ->assertExitCode(0);
    }

    /**
     * @test
     *
     * @define-env runsInProduction
     */
    public function it_asks_for_confirmation_in_production_environments()
    {
        $this->assertTrue($this->app->isProduction());

        $this->artisan('skeema:init --force')->assertSuccessful();

        $this->artisan('skeema:push')
            ->expectsConfirmation('Running skeema push in production. Proceed?', 'no')
            ->expectsOutput('Command cancelled.')
            ->assertExitCode(1);
    }

    /**
     * @test
     *
     * @define-env runsInProduction
     */
    public function it_doesnt_asks_for_confirmation_in_production_environments_with_force_flag()
    {
        $this->assertTrue($this->app->isProduction());

        $this->artisan('skeema:init --force')->assertSuccessful();

        $this->artisan('skeema:push --force')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_errors_were_found()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-remove-column-sql')
        );

        $this->artisan('skeema:push')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaPushFatalErrorException())->getExitCode()
            );
    }

    /** @test */
    public function it_uses_the_alt_wrapper_command_if_configured()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->overwriteSkeemaFile(
            'test1.sql',
            file_get_contents(__DIR__.'/../stubs/test1-new-column-sql')
        );

        config()->set('skeema.alter_wrapper.enabled', true);
        config()->set('skeema.alter_wrapper.bin', 'gh-ost');
        config()->set('skeema.alter_wrapper.min_size', 0);
        config()->set('skeema.alter_wrapper.params', [
            '--max-load=Threads_running=25',
            '--critical-load=Threads_running=1000',
        ]);

        $this->artisan('skeema:push')
            ->expectsOutputToContain("skeema push laravel --alter-wrapper='gh-ost --execute --alter {CLAUSES} --database={SCHEMA} --table={TABLE} --host={HOST} --user={USER} --password={PASSWORDX} --max-load=Threads_running=25 --critical-load=Threads_running=1000' --alter-wrapper-min-size='0' ")
            ->run();
    }
}
