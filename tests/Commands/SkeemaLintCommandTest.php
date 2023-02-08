<?php

namespace Tests\Commands;

use Smakecloud\Skeema\Commands\SkeemaBaseCommand;
use Tests\TestCase;

class SkeemaLintCommandTest extends TestCase
{
    /** @test */
    public function it_exits_with_the_correct_error_code_if_skeema_config_could_not_be_found()
    {
        $this->artisan('skeema:pull')
            ->assertExitCode(SkeemaBaseCommand::ERROR_CODES['SKEEMA_CONFIG_NOT_FOUND']);
    }

    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->artisan('skeema:lint')
            ->assertExitCode(0);
    }
}
