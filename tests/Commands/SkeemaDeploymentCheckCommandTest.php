<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SkeemaDeploymentCheckCommandTest extends TestCase
{
    /** @test */
    public function it_ignores_existing_migrations_if_the_option_is_set()
    {
        $testMigrationFile = database_path('migrations/2019_01_01_000000_test_migration.php');

        File::put($testMigrationFile, 'test');

        $this->artisan('skeema:deployment-check --ignore-migrations')
            ->expectsOutputToContain('Skeema CI check passed!')
            ->assertExitCode(0);

        File::delete($testMigrationFile);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_there_are_existing_laravel_migration_files()
    {
        $testMigrationFile = database_path('migrations/2019_01_01_000000_test_migration.php');

        File::put($testMigrationFile, 'test');

        $this->artisan('skeema:deployment-check')
            ->expectsOutputToContain('Skeema CI check failed! Laravel migrations exist')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\ExistingMigrationsException())->getExitCode()
            );

        File::delete($testMigrationFile);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_there_is_an_existing_sql_dump_file()
    {
        $testDumpFile = database_path('schema/mysql-schema.dump');

        //ensure dir exists
        File::ensureDirectoryExists(database_path('schema'));

        File::put($testDumpFile, 'test');

        $this->artisan('skeema:deployment-check --ignore-migrations')
            ->expectsOutputToContain('Skeema CI check failed! Laravel sql dumpfile exist')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\ExistingDumpFileException())->getExitCode()
            );

        File::delete($testDumpFile);
    }

    /** @test */
    public function it_ignores_existing_dump_file_if_the_option_is_set()
    {
        $testDumpFile = database_path('schema/mysql-schema.dump');

        //ensure dir exists
        File::ensureDirectoryExists(database_path('schema'));

        File::put($testDumpFile, 'test');

        $this->artisan('skeema:deployment-check --ignore-migrations --ignore-dump-file')
            ->expectsOutputToContain('Skeema CI check passed!')
            ->assertExitCode(0);

        File::delete($testDumpFile);
    }

    /** @test */
    public function it_exits_with_the_correct_error_code_if_there_are_running_ghost_migrations()
    {
        File::put('/tmp/gh-ost.test.table.sql', 'test');

        $this->artisan('skeema:deployment-check --ignore-migrations --ignore-dump-file')
            ->expectsOutputToContain('Skeema CI check failed! Found running gh-ost migrations')
            ->assertExitCode(
                (new \Smakecloud\Skeema\Exceptions\RunningGhostMigrationsException())->getExitCode()
            );

        File::delete('/tmp/gh-ost.test.table.sql');
    }

    /** @test */
    public function it_ignores_running_ghost_migrations_if_the_option_is_set()
    {
        File::put('/tmp/gh-ost.test.table.sql', 'test');

        $this->artisan('skeema:deployment-check --ignore-migrations --ignore-ghost-migrations')
            ->expectsOutputToContain('Skeema CI check passed!')
            ->assertExitCode(0);

        File::delete('/tmp/gh-ost.test.table.sql');
    }
}
