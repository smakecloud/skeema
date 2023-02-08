<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaPushCommandTest extends TestCase
{
    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:push')
            ->assertExitCode(0);
    }

    /**
     * @test
     * @define-env runsInProduction
     */
    public function it_asks_for_confirmation_in_production_environments()
    {
        $this->assertTrue($this->app->isProduction());

        $this->artisan('skeema:push')
            ->expectsConfirmation('Running skeema push in production. Proceed?', 'no')
            ->expectsOutput('Command cancelled.')
            ->assertExitCode(1);
    }

    /**
     * @test
     * @define-env runsInProduction
     */
    public function it_doesnt_asks_for_confirmation_in_production_environments_with_force_flag()
    {
        $this->assertTrue($this->app->isProduction());

        $this->artisan('skeema:push --force')
            ->assertExitCode(0);
    }
}
