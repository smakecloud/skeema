<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Smakecloud\Skeema\Exceptions\SkeemaPushCouldNotUpdateTableException;
use Smakecloud\Skeema\Exceptions\SkeemaPushFatalErrorException;
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
        .' {--connection=}';

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
        $options = [
            'temp-schema', 'temp-schema-threads', 'temp-schema-binlog', 'alter-algorithm',
            'alter-lock', 'alter-validate-virtual', 'compare-metadata', 'exact-match',
            'partitioning', 'strip-definer', 'allow-unsafe', 'skip-verify', 'dry-run',
            'foreign-key-checks', 'allow-auto-inc', 'allow-charset', 'allow-compression',
            'allow-definer', 'allow-engine', 'safe-below-size'
        ];

        $args = collect($options)->mapWithKeys(function ($option) {
            $value = $this->option($option);
            if ($value && ($option !== 'temp-schema-threads' || is_numeric($value))) {
                return [$option => $value];
            }
            return [];
        })->toArray();

        $args['temp-schema'] = $args['temp-schema'] ?? $this->getTempSchemaName();
        $args['skip-lint'] = $this->option('skip-lint') || !$this->areLintRulesValid();

        if ($this->getConfig('skeema.alter_wrapper.enabled', false)) {
            $args['alter-wrapper'] = $this->getAlterWrapperCommand();
            $args['alter-wrapper-min-size'] = $this->getConfig(('skeema.alter_wrapper.min_size'), '0');
        }

        if (!$args['skip-lint']) {
            $baseRules = $this->getConfig('skeema.lint.rules', []);
            $pushRules = $this->getConfig('skeema.lint.push', []);

            collect(array_merge($baseRules, $pushRules))->each(function ($value, $key) use (&$args) {
                $option = $this->laravel->make($key)->getOptionString();

                if ($option) {
                    $args[$option] = $value;
                }
            });
        }

        return $args;
    }

    /**
     * Check if the lint rules are valid.
     *
     * @return bool
     */
    private function areLintRulesValid(): bool
    {
        $baseRules = $this->getConfig('skeema.lint.rules', []);
        $pushRules = $this->getConfig('skeema.lint.push', []);

        return !($pushRules === false || !is_array($pushRules) || !is_array($baseRules));
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/lint/
     *
     * @throws \Smakecloud\Skeema\Exceptions\SkeemaPushFatalErrorException
     * @throws \Smakecloud\Skeema\Exceptions\SkeemaPushCouldNotUpdateTableException
     */
    protected function onError(Process $process): void
    {
        if ($process->getExitCode() >= 2) {
            throw new SkeemaPushFatalErrorException();
        } elseif ($process->getExitCode() === 1 && !$this->option('dry-run')) {
            // @codeCoverageIgnoreStart
            throw new SkeemaPushCouldNotUpdateTableException();
            // @codeCoverageIgnoreEnd
        }
    }
}
