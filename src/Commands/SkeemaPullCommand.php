<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;

/**
 * Class SkeemaPullCommand
 * Runs the skeema pull command to pull the database schema into the schema files.
 */
class SkeemaPullCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:pull'
        .' {--skip-format : Skip Reformat SQL statements to match canonical SHOW CREATE}'
        .' {--include-auto-inc : Include starting auto-inc values in new table files, and update in existing files}'
        .' {--new-schemas : Detect any new schemas and populate new dirs for them (enabled by default; disable with skip-new-schemas)}'
        .' {--strip-definer= : Omit DEFINER clauses when writing procs, funcs, views, and triggers to filesystem}'
        .' {--strip-partitioning : Omit PARTITION BY clause when writing partitioned tables to filesystem}'
        .' {--update-views : Update definitions of existing views, using canonical form}'
        .' {--update-partitioning : Update PARTITION BY clauses in existing table files}'
        .' {--connection=}';

    protected $description = 'Pull the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('pull '.static::SKEEMA_ENV_NAME, $this->makeArgs());
    }

    /**
     * Make the arguments for the skeema command.
     *
     * @return array<string, mixed>
     */
    private function makeArgs(): array
    {
        $args = [];

        collect([
            'skip-format',
            'include-auto-inc',
            'new-schemas',
            'strip-partitioning',
            'update-views',
            'update-partitioning',
        ])->filter(fn (string $option) => $this->option($option))
            ->each(function (string $option) use (&$args): void {
                $args[$option] = true;
            });

        if ($this->option('strip-definer')) {
            $args['strip-definer'] = $this->option('strip-definer');
        }

        return $args;
    }
}
