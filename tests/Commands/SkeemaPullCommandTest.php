<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaPullCommandTest extends TestCase
{
    /** @test */
    public function it_exits_with_the_correct_error_code_if_skeema_config_could_not_be_found()
    {
        $this->artisan('skeema:pull')
            ->expectsOutputToContain('Skeema config file not found at')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException(''))->getExitCode()
            );
    }

    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->artisan('skeema:pull')
            ->assertExitCode(0);
    }
}
