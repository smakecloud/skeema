<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Smakecloud\Skeema\Exceptions\SkeemaPushCouldNotUpdateTableException;
use Smakecloud\Skeema\Exceptions\SkeemaPushFatalErrorException;
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
     * Make the arguments for the skeema push command.
     *
     * @return array<string, mixed>
     */
    private function makeArgs(): array
    {
        $args = [];

        collect([
            'alter-algorithm',
            'alter-lock',
            'alter-validate-virtual',
            'compare-metadata',
            'exact-match',
            'partitioning',
            'strip-definer',
            'allow-auto-inc',
            'allow-charset',
            'allow-compression',
            'allow-definer',
            'allow-engine',
            'safe-below-size',
        ])
            ->filter(fn (string $option) => $this->option($option))
            ->each(function (string $option) use (&$args): void {
                $args[$option] = $this->option($option);
            });

        collect([
            'allow-unsafe',
            'skip-verify',
            'dry-run',
            'foreign-key-checks',
        ])
            ->filter(fn (string $option) => $this->option($option))
            ->each(function (string $option) use (&$args): void {
                $args[$option] = true;
            });

        if ($this->getConfig('skeema.alter_wrapper.enabled', false)) {
            $args['alter-wrapper'] = $this->getAlterWrapperCommand();
            $args['alter-wrapper-min-size'] = $this->getConfig(('skeema.alter_wrapper.min_size'), '0');
        }

        $baseRules = $this->getConfig('skeema.lint.rules', []);
        $pushRules = $this->getConfig('skeema.lint.push', []);

        match (true) {
            $this->option('skip-lint') => $args['skip-lint'] = true,
            $pushRules === false => $args['skip-lint'] = true,
            ! is_array($pushRules) => $args['skip-lint'] = true,
            ! is_array($baseRules) => $args['skip-lint'] = true,
            default => collect(array_merge($baseRules, $pushRules))->each(function ($value, $key) use (&$args) {
                $option = $this->laravel->make($key)->getOptionString();

                if ($option) {
                    $args[$option] = $value;
                }
            }),
        };

        return $args;
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
        }

        // @codeCoverageIgnoreStart
        throw new SkeemaPushCouldNotUpdateTableException();
        // @codeCoverageIgnoreEnd
    }
}
