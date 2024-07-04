<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\ParallelTesting;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaPushCommand
 * Runs the skeema push command to push the schema files into the database.
 */
class SkeemaPushCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:push'
        .' {--alter-algorithm= : Apply an ALGORITHM clause to all ALTER TABLEs}'
        .' {--alter-lock= : Apply a LOCK clause to all ALTER TABLEs}'
        .' {--alter-validate-virtual : Apply a WITH VALIDATION clause to ALTER TABLEs affecting virtual columns}'
        .' {--compare-metadata : For stored programs, detect changes to creation-time sql_mode or DB collation}'
        .' {--exact-match : Follow *.sql table definitions exactly, even for differences with no functional impact}'
        .' {--partitioning= : Specify handling of partitioning status on the database side}'
        .' {--strip-definer= : Ignore DEFINER clauses when comparing procs, funcs, views, or triggers}'
        .' {--allow-auto-inc= : List of allowed auto_increment column data types for lint-auto-inc}'
        .' {--allow-charset= :	List of allowed character sets for lint-charset}'
        .' {--allow-compression= : List of allowed compression settings for lint-compression}'
        .' {--allow-definer= : List of allowed routine definers for lint-definer}'
        .' {--allow-engine= : List of allowed storage engines for lint-engine}'
        .' {--allow-unsafe : Permit generating ALTER or DROP operations that are potentially destructive}'
        .' {--safe-below-size= : Always permit generating destructive operations for tables below this size in bytes}'
        .' {--skip-verify : Skip Test all generated ALTER statements on temp schema to verify correctness}'
        .' {--skip-lint : Skip Check modified objects for problems before proceeding}'
        .' {--dry-run : Output DDL but don’t run it; equivalent to skeema diff}'
        .' {--foreign-key-checks : Force the server to check referential integrity of any new foreign key}'
        .' {--temp-schema= : This option specifies the name of the temporary schema to use for Skeema workspace operations.}'
        .' {--temp-schema-threads= : This option controls the concurrency level for CREATE queries when populating the workspace, as well as DROP queries when cleaning up the workspace.}'
        .' {--temp-schema-binlog= : This option controls whether or not workspace operations are written to the database’s binary log, which means they will be executed on replicas if replication is configured.}'
        .' {--force}'
        .' {--connection= : The database connection to use.}'
        .' {--dir= : The directory where the skeema files are stored.}';

    protected $description = 'Push the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->confirmToProceed('Running skeema push in production.');
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('push '.static::SKEEMA_ENV_NAME, $this->makeArgs());
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
     * Make the arguments for the skeema push command.
     *
     * @return array<string, mixed>
     */
    private function makeArgs(): array
    {
        $args = collect([
            'temp-schema' => $this->option('temp-schema') ?: $this->getTempSchemaName(),
            'temp-schema-threads' => is_numeric($this->option('temp-schema-threads')) ? $this->option('temp-schema-threads') : null,
            'temp-schema-binlog' => $this->option('temp-schema-binlog'),
            'alter-algorithm' => $this->option('alter-algorithm'),
            'alter-lock' => $this->option('alter-lock'),
            'alter-validate-virtual' => $this->option('alter-validate-virtual'),
            'compare-metadata' => $this->option('compare-metadata'),
            'exact-match' => $this->option('exact-match'),
            'partitioning' => $this->option('partitioning'),
            'strip-definer' => $this->option('strip-definer'),
            'allow-unsafe' => $this->option('allow-unsafe') ? true : null,
            'skip-verify' => $this->option('skip-verify') ? true : null,
            'dry-run' => $this->option('dry-run') ? true : null,
            'foreign-key-checks' => $this->option('foreign-key-checks') ? true : null,
            'allow-auto-inc' => $this->option('allow-auto-inc'),
            'allow-charset' => $this->option('allow-charset'),
            'allow-compression' => $this->option('allow-compression'),
            'allow-definer' => $this->option('allow-definer'),
            'allow-engine' => $this->option('allow-engine'),
            'safe-below-size' => $this->option('safe-below-size'),
        ])
        ->filter()
        ->merge($this->getLintArgs())
        ->filter()
        ->toArray();

        if ($this->getConfig('skeema.alter_wrapper.enabled', false)) {
            return collect($args)
                ->merge([
                    'alter-wrapper' => $this->getAlterWrapperCommand(),
                    'alter-wrapper-min-size' => $this->getConfig('skeema.alter_wrapper.min_size', '0'),
                ])
                ->toArray();
        }

        return $args;
    }

    /**
     * Get the arguments to run Skeema's lint command, based on the configuration.
     *
     * If the 'skeema.lint.diff' or 'skeema.lint.rules' configuration values are not set or invalid,
     * return an array indicating that linting should be skipped.
     *
     * The method merges the base and diff rules, creates an array of options from them.
     *
     * @return array<string, mixed> The associative array of options to run Skeema's lint command with.
     */
    private function getLintArgs(): array
    {
        $baseRules = $this->getConfig('skeema.lint.rules', []);
        $diffRules = $this->getConfig('skeema.lint.diff', []);

        if ($this->option('skip-lint') || $diffRules === false || ! is_array($diffRules) || ! is_array($baseRules)) {
            return ['skip-lint' => true];
        }

        return collect($baseRules)->merge($diffRules)
            ->mapWithKeys(function ($value, $key) {
                return [$this->laravel->make($key)->getOptionString() => $value];
            })
            ->toArray();
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/lint/
     */
    protected function onError(Process $process): void
    {
        if ($process->getExitCode() < 1) {
            return;
        }

        if ($process->getExitCode() >= 2) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaPushFatalErrorException();
        }

        if (! $this->option('dry-run')) {
            // @codeCoverageIgnoreStart
            throw new \Smakecloud\Skeema\Exceptions\SkeemaPushCouldNotUpdateTableException();
            // @codeCoverageIgnoreEnd
        }
    }
}
