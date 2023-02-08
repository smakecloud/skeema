<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaLintCommandTest extends TestCase
{
    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:lint')
            ->assertExitCode(0);
    }
}
