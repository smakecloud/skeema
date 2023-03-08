<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
//use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SkeemaMigrateAndPullCommandTest extends TestCase
{
    private function createSkeemaTestFile()
    {
        $testSkeemaFile = database_path('skeema/test2.sql');
        File::ensureDirectoryExists(database_path('skeema'));
        File::put($testSkeemaFile, file_get_contents(__DIR__.'/../stubs/test2-sql'));
    }

    private function deleteSkeemaTestFile()
    {
        $testSkeemaFile = database_path('skeema/test2.sql');

        if(File::exists($testSkeemaFile)) {
            File::delete($testSkeemaFile);
        }
    }

    private function skeemaTestFileExists(): bool
    {
        $testSkeemaFile = database_path('skeema/test2.sql');
        return File::exists($testSkeemaFile);
    }

    private function createMigrationTestFile()
    {
        $testMigrationFile = database_path('migrations/2022_10_26_132948_create_test2_table.php');
        File::ensureDirectoryExists(database_path('migrations'));
        File::put($testMigrationFile, file_get_contents(__DIR__.'/../stubs/test2-php'));
    }

    private function deleteMigrationTestFile()
    {
        $testMigrationFile = database_path('migrations/2022_10_26_132948_create_test2_table.php');

        if(File::exists($testMigrationFile)) {
            File::delete($testMigrationFile);
        }
    }

    private function migrationTestFileExists(): bool
    {
        $testMigrationFile = database_path('migrations/2022_10_26_132948_create_test2_table.php');
        return File::exists($testMigrationFile);
    }

    protected function connectionsToTransact()
    {
        return array_filter(parent::connectionsToTransact(), function ($connection) {
            return $connection !== null;
        });
    }

    /** @test */
    public function it_converts_laravel_migrations_into_skeema_files()
    {
        $this->artisan('skeema:init')->assertSuccessful();

        $this->assertTrue(
            Schema::hasTable('migrations'),
            'Migrations table should exist'
        );
        $this->assertTrue(
            Schema::hasTable('test1'),
            'Test1 table should exist'
        );

        $this->createMigrationTestFile();
        $this->deleteTest1TableMigrationFile();

        $this->artisan('skeema:migrate-and-pull')
            ->assertExitCode(0);

        $this->assertTrue(
            Schema::hasTable('migrations'),
            'Migrations table should exist'
        );
        $this->assertTrue(
            Schema::hasTable('test1'),
            'Test1 table should exist'
        );
        $this->assertTrue(
            Schema::hasTable('test2'),
            'Test2 table should exist'
        );
        $this->assertFalse(
            $this->migrationTestFileExists(),
            'Migration file for table2 should not exist'
        );
        $this->assertTrue(
            $this->skeemaTestFileExists(),
            'Skeema file for table2 should exist'
        );

        $this->deleteSkeemaTestFile();
    }

    /** @test */
    public function it_keeps_migrations_if_option_is_set()
    {
        Schema::dropIfExists('test2');
        $this->artisan('skeema:init')->assertSuccessful();

        $this->assertTrue(
            Schema::hasTable('migrations'),
            'Migrations table should exist'
        );
        $this->assertTrue(
            Schema::hasTable('test1'),
            'Test1 table should exist'
        );

        $this->createMigrationTestFile();
        $this->deleteTest1TableMigrationFile();

        $this->artisan('skeema:migrate-and-pull --keep-migrations')
            ->assertExitCode(0);

        $this->assertTrue(
            Schema::hasTable('migrations'),
            'Migrations table should exist'
        );
        $this->assertTrue(
            Schema::hasTable('test1'),
            'Test1 table should exist'
        );
        $this->assertTrue(
            Schema::hasTable('test2'),
            'Test2 table should exist'
        );
        $this->assertTrue(
            $this->migrationTestFileExists(),
            'Migration file for table2 should not have been deleted'
        );
        $this->assertTrue(
            $this->skeemaTestFileExists(),
            'Skeema file for table2 should exist'
        );

        $this->deleteSkeemaTestFile();
    }

    /** @test */
    public function it_does_not_push_the_initial_skeema_schema_if_option_is_set()
    {
        Schema::dropIfExists('test2');
        $this->deleteSkeemaTestFile();
        $this->deleteMigrationTestFile();

        $this->artisan('skeema:init')->assertSuccessful();

        $this->createSkeemaTestFile();

        $this->artisan('skeema:migrate-and-pull --no-push')
            ->assertExitCode(0);

        $this->assertFalse(
            Schema::hasTable('test2'),
            'Test2 table should not exist'
        );

        $this->deleteSkeemaTestFile();
    }

}
