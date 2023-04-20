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

}
