<?php

namespace Tests\Commands;

use Tests\TestCase;

class SkeemaPullCommandTest extends TestCase
{
    /** @test */
    public function it_executes_the_command()
    {
        $this->artisan('skeema:pull')
            ->assertExitCode(0);
    }
}
