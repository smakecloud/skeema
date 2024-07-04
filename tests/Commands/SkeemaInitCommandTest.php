<?php

namespace Tests\Commands;

use Illuminate\Support\Str;
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

        $stub = $this->getStub('skeema-config');
        $stub = str_replace(
            '$$SKEEMA_VERSION$$',
            $this->getSkeemaVersionString(),
            $stub
        );
        $stub = str_replace(
            '$$SKEEMA_FLAVOR$$',
            $this->getDbFlavor(),
            $stub
        );

        $this->assertStringEqualsFile(
            $this->getSkeemaDir().'/.skeema',
            $stub
        );
    }

    private function getDbFlavor(): string
    {
        $queryResult = $this->getConnection()->select('SHOW VARIABLES LIKE "version"');

        return Str::of($queryResult[0]->Value)
            ->before('-')
            ->prepend(strtolower($this->getConnection()->getDriverName()) . ':')
            ->__toString();
    }

    /** @test */
    public function it_generated_sql_schemas()
    {
        $this->artisan('skeema:init')
            ->assertSuccessful();

        $this->assertFileExists($this->getSkeemaDir().'/migrations.sql');
        $this->assertFileExists($this->getSkeemaDir().'/test1.sql');

        $this->assertFileEquals(
            __DIR__.'/../stubs/migrations-sql',
            $this->getSkeemaDir().'/migrations.sql'
        );
        $this->assertFileEquals(
            __DIR__.'/../stubs/test1-sql',
            $this->getSkeemaDir().'/test1.sql'
        );
    }
}
