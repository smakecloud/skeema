<?php

namespace Smakecloud\Skeema\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Class SkeemaMigrateAndPullCommand
 * Pushes the skeema, runs migrations, patches skeema schema files and removes migrations
 */
class SkeemaMigrateAndPullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skeema:migrate-and-pull'
        . ' {--keep-migrations : Keep migrations after skeema pull}'
        . ' {--no-push : Skip the initial pushing of skeema files to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pushes the skeema, runs migrations, patches skeema schema files and removes migrations';

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
    public function handle()
    {
        $files = collect($this->migrator->getMigrationFiles($this->getMigrationPath()));

        if ($files->count() > 0) {
            if(! $this->option('no-push')) {
                $status = $this->call('skeema:push', [
                    '--force' => true,
                    '--allow-unsafe' => true,
                    '--skip-lint' => true,
                ]);
                // @codeCoverageIgnoreStart
                if ($status !== 0) {
                    throw new Exception(
                        'skeema:push failed with exit code: ' . $status
                        . ' Files: ' . implode(', ', $files->keys()->toArray())
                    );
                }
                // @codeCoverageIgnoreEnd
            }

            $ran = collect($this->migrator->getRepository()->getRan());

            $files->each(function ($file, $key) use ($ran) {
                if (! $this->option('keep-migrations') && $ran->contains($key)) {
                    $this->warn("Deleting ran migration: {$key} {$file}");

                    $this->filesystem->delete($file);
                } else {
                    $this->filesystem->requireOnce($file);

                    $this->migrator->runPending([$file]);

                    $this->info("Ran migration: {$key} {$file}");

                    if(! $this->option('keep-migrations')) {
                        $this->warn("Deleting ran migration: {$key} {$file}");

                        $this->filesystem->delete($file);
                    }
                }
            });

            $status = $this->call('skeema:pull');
            // @codeCoverageIgnoreStart
            if ($status !== 0) {
                throw new Exception('skeema:pull failed with exit code: '.$status);
            }
            // @codeCoverageIgnoreEnd

            $this->info('Done!');

            return 0;
        }
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

}
