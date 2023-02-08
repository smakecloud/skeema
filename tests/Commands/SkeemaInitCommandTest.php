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

    /** @test */
    public function it_generated_a_skeema_config()
    {
        $this->artisan('skeema:init')
            ->execute();

        $this->assertFileExists($this->getSkeemaDir() . '/.skeema');
        $this->assertFileEquals(
            __DIR__ . '/../stubs/skeema-config',
            $this->getSkeemaDir() . '/.skeema'
        );
    }

    /** @test */
    public function it_generated_sql_schemas()
    {
        $this->artisan('skeema:init')
            ->execute();

        $this->assertFileExists($this->getSkeemaDir() . '/migrations.sql');
        $this->assertFileExists($this->getSkeemaDir() . '/test1.sql');

        $this->assertFileEquals(
            __DIR__ . '/../stubs/migrations-sql',
            $this->getSkeemaDir() . '/migrations.sql'
        );
        $this->assertFileEquals(
            __DIR__ . '/../stubs/test1-sql',
            $this->getSkeemaDir() . '/test1.sql'
        );
    }
}
