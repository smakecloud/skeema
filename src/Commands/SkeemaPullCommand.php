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
        $options = [
            'temp-schema', 'temp-schema-threads', 'temp-schema-binlog', 'skip-format',
            'include-auto-inc', 'new-schemas', 'strip-definer', 'strip-partitioning',
            'update-views', 'update-partitioning'
        ];

        $args = collect($options)->mapWithKeys(function ($option) {
            $value = $this->option($option);
            if ($value && ($option !== 'temp-schema-threads' || is_numeric($value))) {
                return [$option => $value];
            }
            return [];
        })->toArray();

        $args['temp-schema'] = $args['temp-schema'] ?? $this->getTempSchemaName();
        $args['skip-format'] = $args['skip-format'] ?? false;
        $args['include-auto-inc'] = $args['include-auto-inc'] ?? false;
        $args['new-schemas'] = $args['new-schemas'] ?? false;
        $args['strip-partitioning'] = $args['strip-partitioning'] ?? false;
        $args['update-views'] = $args['update-views'] ?? false;
        $args['update-partitioning'] = $args['update-partitioning'] ?? false;

        return $args;
    }
}
