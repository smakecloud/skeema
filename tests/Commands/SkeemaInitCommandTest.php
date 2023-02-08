<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaInitCommandTest extends TestCase
{
    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:init')
            ->assertExitCode(0);
    }
}
