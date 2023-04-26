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
        .' {--connection=}';

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
        $args = [];

        if ($this->option('temp-schema')) {
            $args['temp-schema'] = $this->option('temp-schema');
        } else {
            $args['temp-schema'] = $this->getTempSchemaName();
        }

        if ($this->option('temp-schema-threads') && is_numeric($this->option('temp-schema-threads'))) {
            $args['temp-schema-threads'] = $this->option('temp-schema-threads');
        }

        if ($this->option('temp-schema-binlog')) {
            $args['temp-schema-binlog'] = $this->option('temp-schema-binlog');
        }

        if ($this->option('skip-format')) {
            $args['skip-format'] = true;
        }

        if ($this->option('include-auto-inc')) {
            $args['include-auto-inc'] = true;
        }

        if ($this->option('new-schemas')) {
            $args['new-schemas'] = true;
        }

        if ($this->option('strip-definer')) {
            $args['strip-definer'] = $this->option('strip-definer');
        }

        if ($this->option('strip-partitioning')) {
            $args['strip-partitioning'] = true;
        }

        if ($this->option('update-views')) {
            $args['update-views'] = true;
        }

        if ($this->option('update-partitioning')) {
            $args['update-partitioning'] = true;
        }

        return $args;
    }
}
