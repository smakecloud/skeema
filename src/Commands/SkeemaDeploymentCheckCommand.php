<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Smakecloud\Skeema\Exceptions\ExceptionWithExitCode;
use Smakecloud\Skeema\Exceptions\ExistingDumpFileException;
use Smakecloud\Skeema\Exceptions\ExistingMigrationsException;
use Smakecloud\Skeema\Exceptions\RunningGhostMigrationsException;

/**
 * Class SkeemaDeploymentCheckCommand
 * Checks for existing laravel migrations, dump-file, or running ghost migrations.
 */
class SkeemaDeploymentCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skeema:deployment-check'
        .' {--ignore-migrations : Ignore existing migrations}'
        .' {--ignore-dump-file : Ignore existing dump-file}'
        .' {--ignore-ghost-migrations : Ignore running ghost migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if there are any pending migrations, existing dump-file, or running ghost migrations';

    /** @var \Illuminate\Filesystem\Filesystem */
    private $filesystem;

    /** @var \Illuminate\Database\Migrations\Migrator */
    private $migrator;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->migrator = app('migrator');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $exitCode = 0;

        try {
            $this->ensureThatAnExistingMigrationWillBeIgnoredIfDesired();
            $this->ensureThatAnExistingDumpFileWillBeIgnoredIfDesired();
            $this->ensureThatRunningGhostMigrationsWillBeIgnoredIfDesired();

            $this->info('Skeema CI check passed!');
        } catch (ExceptionWithExitCode $e) {
            $this->error('Skeema CI check failed! '.$e->getMessage());

            $exitCode = $e->getExitCode();
        }

        return $exitCode;
    }

    /**
     * Get the path to the migration directory.
     */
    protected function getMigrationPath(): string
    {
        return database_path('migrations');
    }

    /**
     * Get the path to the classic database schema file.
     */
    protected function getDatabaseSchemaPath(): string
    {
        return database_path('schema/mysql-schema.dump');
    }

    /**
     * @throws \Smakecloud\Skeema\Exceptions\ExistingMigrationsException
     */
    private function ensureThatAnExistingMigrationWillBeIgnoredIfDesired(): void
    {
        if ($this->option('ignore-migrations')) {
            return;
        }

        if ($this->hasNotClassicMigrations()) {
            return;
        }

        throw new ExistingMigrationsException();
    }

    /**
     * @throws \Smakecloud\Skeema\Exceptions\ExistingDumpFileException
     */
    private function ensureThatAnExistingDumpFileWillBeIgnoredIfDesired(): void
    {
        if ($this->option('ignore-dump-file')) {
            return;
        }

        if ($this->hasNotClassicDatabaseSchema()) {
            return;
        }

        throw new ExistingDumpFileException();
    }

    /**
     * @throws \Smakecloud\Skeema\Exceptions\RunningGhostMigrationsException
     */
    private function ensureThatRunningGhostMigrationsWillBeIgnoredIfDesired(): void
    {
        if ($this->option('ignore-ghost-migrations')) {
            return;
        }

        if ($this->hasNotRunningGhostMigrations()) {
            return;
        }

        throw new RunningGhostMigrationsException();
    }

    /**
     * Check if there are no classic migration files in the migration directory.
     */
    private function hasNotClassicMigrations(): bool
    {
        return collect($this->migrator->getMigrationFiles($this->getMigrationPath()))
            ->isEmpty();
    }

    /**
     * Check if the classic database schema file exists.
     */
    private function hasClassicDatabaseSchema(): bool
    {
        return $this->filesystem->exists($this->getDatabaseSchemaPath());
    }

    /**
     * Check if the classic database schema file does not exist.
     */
    private function hasNotClassicDatabaseSchema(): bool
    {
        return ! $this->hasClassicDatabaseSchema();
    }

    /**
     * Check if there are no running ghost migrations.
     */
    private function hasNotRunningGhostMigrations(): bool
    {
        return collect($this->filesystem->glob('/tmp/gh-ost*'))->isEmpty();
    }
}
