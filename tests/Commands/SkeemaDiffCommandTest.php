<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaDiffCommandTest extends TestCase
{
    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:diff')
            ->assertExitCode(0);
    }
}
