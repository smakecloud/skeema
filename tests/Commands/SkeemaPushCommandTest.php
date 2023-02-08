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
}
