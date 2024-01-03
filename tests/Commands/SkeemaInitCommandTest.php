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

    /**
     * @test
     *
     * @define-env runsInProduction
     */
    public function it_asks_for_confirmation_in_production_environments()
    {
        $this->assertTrue($this->app->isProduction());

        $this->artisan('skeema:init')
            ->expectsOutput('Attention - Your consent has significant implications.')
            ->expectsConfirmation('Running skeema init will overwrite any existing schema files. Proceed?', 'no')
            ->expectsOutput('Command cancelled.')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\CommandCancelledException())->getExitCode()
            );

        $this->artisan('skeema:init')
            ->expectsOutput('Attention - Your consent has significant implications.')
            ->expectsConfirmation('Running skeema init will overwrite any existing schema files. Proceed?', 'yes')
            ->assertExitCode(0);
    }

    /**
     * @test
     *
     * @define-env runsInProduction
     */
    public function it_doesnt_asks_for_confirmation_in_production_environments_with_force_flag()
    {
        $this->assertTrue($this->app->isProduction());

        $this->artisan('skeema:init --force')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_generated_a_skeema_config()
    {
        $this->artisan('skeema:init')
            ->assertSuccessful();

        $this->assertFileExists($this->getSkeemaDir().'/.skeema');

        $stub = match (true) {
            $this->connectionIsMariaDB() => $this->getStub('skeema-config-maria'),
            $this->connectionIsMySQL5() => $this->getStub('skeema-config'),
            $this->connectionIsMySQL8() => $this->getStub('skeema-config-mysql8'),
            default => null,
        };

        $stub = str_replace(
            '$$SKEEMA_VERSION$$',
            $this->getSkeemaVersionString(),
            $stub
        );

        $this->assertStringEqualsFile(
            $this->getSkeemaDir().'/.skeema',
            $stub
        );
    }

    /** @test */
    public function it_generated_sql_schemas()
    {
        $this->artisan('skeema:init')
            ->assertSuccessful();

        $this->assertFileExists($this->getSkeemaDir().'/migrations.sql');
        $this->assertFileExists($this->getSkeemaDir().'/test1.sql');

        $expected = match (true) {
            $this->connectionIsMariaDB() => [
                'migrations' => __DIR__.'/../stubs/migrations-maria-sql',
                'test1' => __DIR__.'/../stubs/test1-maria-sql',
            ],
            $this->connectionIsMySQL5() => [
                'migrations' => __DIR__.'/../stubs/migrations-sql',
                'test1' => __DIR__.'/../stubs/test1-sql',
            ],
            $this->connectionIsMySQL8() => [
                'migrations' => __DIR__.'/../stubs/migrations-mysql8-sql',
                'test1' => __DIR__.'/../stubs/test1-mysql8-sql',
            ],
            default => null,
        };

        if($expected) {
            $this->assertFileEquals(
                $expected['migrations'],
                $this->getSkeemaDir().'/migrations.sql'
            );
            $this->assertFileEquals(
                $expected['test1'],
                $this->getSkeemaDir().'/test1.sql'
            );
        }
    }
}
