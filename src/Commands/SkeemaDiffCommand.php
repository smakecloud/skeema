<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\ParallelTesting;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaDiffCommand
 * Runs the skeema diff command to compare the database schema with the schema files.
 */
class SkeemaDiffCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:diff'
        .' {--ignore-warnings : No error will be thrown if there are warnings}'
        .' {--alter-algorithm= : The algorithm to use for ALTER TABLE statements}'
        .' {--alter-lock= : The lock to use for ALTER TABLE statements}'
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
        .' {--temp-schema= : This option specifies the name of the temporary schema to use for Skeema workspace operations.}'
        .' {--temp-schema-threads= : This option controls the concurrency level for CREATE queries when populating the workspace, as well as DROP queries when cleaning up the workspace.}'
        .' {--temp-schema-binlog= : This option controls whether or not workspace operations are written to the database’s binary log, which means they will be executed on replicas if replication is configured.}'
        .' {--connection=}';

    protected $description = 'Diff the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('diff '.static::SKEEMA_ENV_NAME, $this->makeArgs());
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/diff/
     */
    protected function onError(Process $process): void
    {
        if ($process->getExitCode() >= 2) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithErrorsException();
        }

        if (! $this->option('ignore-warnings')) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithWarningsException();
        }
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
     * @return array<string, string|bool>
     */
    private function makeArgs(): array
    {
        $args = collect([
            'temp-schema' => $this->option('temp-schema') ?? $this->getTempSchemaName(),
            'temp-schema-threads' => ($this->option('temp-schema-threads') && is_numeric($this->option('temp-schema-threads'))) ? $this->option('temp-schema-threads') : null,
            'temp-schema-binlog' => $this->option('temp-schema-binlog'),
            'alter-algorithm' => $this->option('alter-algorithm'),
            'alter-lock' => $this->option('alter-lock'),
            'alter-validate-virtual' => $this->option('alter-validate-virtual'),
            'compare-metadata' => $this->option('compare-metadata'),
            'exact-match' => $this->option('exact-match'),
            'allow-unsafe' => $this->option('allow-unsafe'),
            'skip-verify' => $this->option('skip-verify'),
            'partitioning' => $this->option('partitioning'),
            'strip-definer' => $this->option('strip-definer'),
            'allow-auto-inc' => $this->option('allow-auto-inc'),
            'allow-charset' => $this->option('allow-charset'),
            'allow-compression' => $this->option('allow-compression'),
            'allow-definer' => $this->option('allow-definer'),
            'allow-engine' => $this->option('allow-engine'),
            'safe-below-size' => $this->option('safe-below-size'),
        ])
        ->filter()
        ->merge($this->getLintArgs())
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
     * @return array The associative array of options to run Skeema's lint command with.
     */
    private function getLintArgs(): array
    {
        $baseRules = $this->getConfig('skeema.lint.rules', []);
        $diffRules = $this->getConfig('skeema.lint.diff', []);

        if ($diffRules === false || ! is_array($diffRules) || ! is_array($baseRules)) {
            return ['skip-lint' => true];
        }

        return collect($baseRules)->merge($diffRules)
            ->mapWithKeys(function ($value, $key) {
                return [$this->laravel->make($key)->getOptionString() => $value];
            })
            ->toArray();
    }
}
