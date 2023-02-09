<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaDiffCommand
 * Runs the skeema diff command to compare the database schema with the schema files.
 */
class SkeemaDiffCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:diff'
        . ' {--ignore-warnings : No error will be thrown if there are warnings}'
        . ' {--alter-algorithm= : The algorithm to use for ALTER TABLE statements}'
        . ' {--alter-lock= : The lock to use for ALTER TABLE statements}'
        . ' {--alter-validate-virtual : Apply a WITH VALIDATION clause to ALTER TABLEs affecting virtual columns}'
        . ' {--compare-metadata : For stored programs, detect changes to creation-time sql_mode or DB collation}'
        . ' {--exact-match : Follow *.sql table definitions exactly, even for differences with no functional impact}'
        . ' {--partitioning= : Specify handling of partitioning status on the database side}'
        . ' {--strip-definer= : Ignore DEFINER clauses when comparing procs, funcs, views, or triggers}'
        . ' {--allow-auto-inc= : List of allowed auto_increment column data types for lint-auto-inc}'
        . ' {--allow-charset= :	List of allowed character sets for lint-charset}'
        . ' {--allow-compression= : List of allowed compression settings for lint-compression}'
        . ' {--allow-definer= : List of allowed routine definers for lint-definer}'
        . ' {--allow-engine= : List of allowed storage engines for lint-engine}'
        . ' {--allow-unsafe : Permit generating ALTER or DROP operations that are potentially destructive}'
        . ' {--safe-below-size= : Always permit generating destructive operations for tables below this size in bytes}'
        . ' {--skip-verify : Skip Test all generated ALTER statements on temp schema to verify correctness}'
        . ' {--connection=}';

    protected $description = 'Diff the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('diff '.static::SKEEMA_ENV_NAME, $this->makeArgs());
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/diff/
     */
    protected function onError(Process $process)
    {
        if ($process->getExitCode() >= 2) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithErrorsException();
        } else {
            if (! $this->option('ignore-warnings')) {
                throw new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithWarningsException();
            }
        }
    }

    private function makeArgs(): array
    {
        $args = [];

        if ($this->option('alter-algorithm')) {
            $args['alter-algorithm'] = $this->option('alter-algorithm');
        }

        if ($this->option('alter-lock')) {
            $args['alter-lock'] = $this->option('alter-lock');
        }

        if ($this->option('alter-validate-virtual')) {
            $args['alter-validate-virtual'] = true;
        }

        if ($this->option('compare-metadata')) {
            $args['compare-metadata'] = true;
        }

        if ($this->option('exact-match')) {
            $args['exact-match'] = true;
        }

        if ($this->option('allow-unsafe')) {
            $args['allow-unsafe'] = true;
        }

        if ($this->option('skip-verify')) {
            $args['skip-verify'] = true;
        }

        if ($this->option('partitioning')) {
            $args['partitioning'] = $this->option('partitioning');
        }

        if ($this->option('strip-definer')) {
            $args['strip-definer'] = $this->option('strip-definer');
        }

        if ($this->option('allow-auto-inc')) {
            $args['allow-auto-inc'] = $this->option('allow-auto-inc');
        }

        if ($this->option('allow-charset')) {
            $args['allow-charset'] = $this->option('allow-charset');
        }

        if ($this->option('allow-compression')) {
            $args['allow-compression'] = $this->option('allow-compression');
        }

        if ($this->option('allow-definer')) {
            $args['allow-definer'] = $this->option('allow-definer');
        }

        if ($this->option('allow-engine')) {
            $args['allow-engine'] = $this->option('allow-engine');
        }

        if ($this->option('safe-below-size')) {
            $args['safe-below-size'] = $this->option('safe-below-size');
        }

        if ($this->getConfig('skeema.alter_wrapper.enabled', false)) {
            $args['alter-wrapper'] = $this->getAlterWrapperCommand();
            $args['alter-wrapper-min-size'] = $this->getConfig(('skeema.alter_wrapper.min_size'), '0');
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
