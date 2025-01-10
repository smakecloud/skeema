<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
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
        .' {--ignore-ghost-migrations : Ignore running ghost migrations}'
        .' {--connection= : The database connection to use}'
        .' {--dir= : The directory where the skeema files are stored}';

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
            if (! $this->option('ignore-migrations') && $this->hasMigrations()) {
                throw new ExistingMigrationsException();
            }

            if (! $this->option('ignore-dump-file') && $this->hasDatabaseSchema()) {
                throw new ExistingDumpFileException();
            }

            if (! $this->option('ignore-ghost-migrations') && $this->hasRunningGhostMigrations()) {
                throw new RunningGhostMigrationsException();
            }
        } catch (\Smakecloud\Skeema\Exceptions\ExceptionWithExitCode $e) {
            $this->error('Skeema CI check failed! '.$e->getMessage());

            $exitCode = $e->getExitCode();
        }

        $this->info('Skeema CI check passed!');

        return $exitCode;
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return database_path('migrations');
    }

    /**
     * Get the path to the classic database schema file.
     *
     * @return string
     */
    protected function getDatabaseSchemaPath()
    {
        return database_path('schema/mysql-schema.dump');
    }

    /**
     * Check if there are any classic migration files in the migration directory.
     */
    private function hasMigrations(): bool
    {
        $files = collect($this->migrator->getMigrationFiles($this->getMigrationPath()));

        return $files->count() > 0;
    }

    /**
     * Check if the classic database schema file exists.
     */
    private function hasDatabaseSchema(): bool
    {
        return $this->filesystem->exists($this->getDatabaseSchemaPath());
    }

    /**
     * Check if any /tmp/gh-ost*
     */
    private function hasRunningGhostMigrations(): bool
    {
        $files = $this->filesystem->glob('/tmp/gh-ost*');

        return count($files) > 0;
    }
}
