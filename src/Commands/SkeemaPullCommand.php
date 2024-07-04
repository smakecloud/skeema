<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\ParallelTesting;

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
        .' {--temp-schema= : This option specifies the name of the temporary schema to use for Skeema workspace operations.}'
        .' {--temp-schema-threads= : This option controls the concurrency level for CREATE queries when populating the workspace, as well as DROP queries when cleaning up the workspace.}'
        .' {--temp-schema-binlog= : This option controls whether or not workspace operations are written to the database’s binary log, which means they will be executed on replicas if replication is configured.}'
        .' {--connection=}'
        .' {--dir= : The directory where the skeema files are stored.}';

    protected $description = 'Pull the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('pull '.static::SKEEMA_ENV_NAME, $this->makeArgs());
    }

    /**
     * Get the temp schema name.
     */
    private function getTempSchemaName(): string
    {
        $parallelTestingToken = ParallelTesting::token();

        if ($parallelTestingToken) {
            return '_skeema_temp_'.$parallelTestingToken;
        }

        return '_skeema_temp';
    }

    /**
     * Make the arguments for the skeema command.
     *
     * @return array<string, mixed>
     */
    private function makeArgs(): array
    {
        return collect([
            'temp-schema' => $this->option('temp-schema') ?: $this->getTempSchemaName(),
            'temp-schema-threads' => ($this->option('temp-schema-threads') && is_numeric($this->option('temp-schema-threads'))) ? $this->option('temp-schema-threads') : null,
            'temp-schema-binlog' => $this->option('temp-schema-binlog'),
            'skip-format' => $this->option('skip-format') ? true : null,
            'include-auto-inc' => $this->option('include-auto-inc') ? true : null,
            'new-schemas' => $this->option('new-schemas') ? true : null,
            'strip-definer' => $this->option('strip-definer'),
            'strip-partitioning' => $this->option('strip-partitioning') ? true : null,
            'update-views' => $this->option('update-views') ? true : null,
            'update-partitioning' => $this->option('update-partitioning') ? true : null,
        ])
        ->filter()
        ->toArray();
    }
}
