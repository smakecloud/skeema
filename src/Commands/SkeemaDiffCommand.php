<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithErrorsException;
use Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithWarningsException;
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
     *
     * @throws \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithErrorsException
     * @throws \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithWarningsException
     */
    protected function onError(Process $process): void
    {
        if ($process->getExitCode() > 1) {
            throw new SkeemaDiffExitedWithErrorsException();
        }

        if (! $this->option('ignore-warnings')) {
            throw new SkeemaDiffExitedWithWarningsException();
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
        $options = [
            'temp-schema', 'temp-schema-threads', 'temp-schema-binlog', 'alter-algorithm',
            'alter-lock', 'alter-validate-virtual', 'compare-metadata', 'exact-match',
            'allow-unsafe', 'skip-verify', 'partitioning', 'strip-definer',
            'allow-auto-inc', 'allow-charset', 'allow-compression', 'allow-definer',
            'allow-engine', 'safe-below-size'
        ];

        $args = collect($options)->mapWithKeys(function ($option) {
            $value = $this->option($option);
            if ($value && ($option !== 'temp-schema-threads' || is_numeric($value))) {
                return [$option => $value];
            }
            return [];
        })->toArray();

        $args['temp-schema'] = $args['temp-schema'] ?? $this->getTempSchemaName();

        if ($this->getConfig('skeema.alter_wrapper.enabled', false)) {
            $args['alter-wrapper'] = $this->getAlterWrapperCommand();
            $args['alter-wrapper-min-size'] = $this->getConfig('skeema.alter_wrapper.min_size', '0');
        }

        $baseRules = $this->getConfig('skeema.lint.rules', []);
        $diffRules = $this->getConfig('skeema.lint.diff', []);

        if ($diffRules === false || !is_array($diffRules) || !is_array($baseRules)) {
            $args['skip-lint'] = true;
            return $args;
        }

        collect(array_merge($baseRules, $diffRules))->each(function ($value, $key) use (&$args) {
            $option = $this->laravel->make($key)->getOptionString();

            if ($option) {
                $args[$option] = $value;
            }
        });

        return $args;
    }
}
