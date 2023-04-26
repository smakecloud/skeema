<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\ParallelTesting;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaLintCommand
 * Runs the skeema lint command to lint the schema files against the database.
 */
class SkeemaLintCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:lint'
        .' {--skip-format : Skip formatting the schema files}'
        .' {--strip-definer= : Remove DEFINER clauses from *.sql files}'
        .' {--strip-partitioning : Remove PARTITION BY clauses from *.sql files}'
        .' {--allow-auto-inc= : List of allowed auto_increment column data types for lint-auto-inc}'
        .' {--allow-charset= :	List of allowed character sets for lint-charset}'
        .' {--allow-compression= : List of allowed compression settings for lint-compression}'
        .' {--allow-definer= : List of allowed routine definers for lint-definer}'
        .' {--allow-engine= : List of allowed storage engines for lint-engine}'
        .' {--update-views : Reformat views in canonical single-line form}'
        .' {--ignore-warnings : Exit with status 0 even if warnings are found}'
        .' {--output-format=default : Output format (default, github, or quiet)}'
        .' {--temp-schema= : This option specifies the name of the temporary schema to use for Skeema workspace operations.}'
        .' {--temp-schema-threads= : This option controls the concurrency level for CREATE queries when populating the workspace, as well as DROP queries when cleaning up the workspace.}'
        .' {--temp-schema-binlog= : This option controls whether or not workspace operations are written to the database’s binary log, which means they will be executed on replicas if replication is configured.}'
        .' {--connection=}';

    protected $description = 'Lint the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('lint '.static::SKEEMA_ENV_NAME, $this->makeArgs());
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
     * Get the lint arguments.
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

        if ($this->option('strip-definer')) {
            $args['strip-definer'] = $this->option('strip-definer');
        }

        if ($this->option('strip-partitioning')) {
            $args['strip-partitioning'] = true;
        }

        if ($this->option('update-views')) {
            $args['update-views'] = true;
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

        return [
            ...$this->lintRules(),
            ...$args,
        ];
    }

    /**
     * Get the lint rules.
     *
     * @return array<string, string>
     */
    private function lintRules()
    {
        /** @var array<string, string> */
        $rules = $this->getConfig('skeema.lint.rules', []);

        return collect($rules)
            ->mapWithKeys(function (string $value, string $key) {
                $option = $this->laravel->make($key)->getOptionString();

                return [$option => $value];
            })->toArray();
    }

    protected function onOutput($type, $buffer)
    {
        if ($this->option('output-format') === 'quiet') {
            // @codeCoverageIgnoreStart
            return;
        // @codeCoverageIgnoreEnd
        } elseif ($this->option('output-format') === 'github') {
            $re = '/^(\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d) \[([A-Z]*)\]\w?(.*\.sql):(\d*):(.*)$/m';

            preg_match_all($re, $buffer, $matches, PREG_SET_ORDER, 0);

            if (blank($matches)) {
                parent::onOutput($type, $buffer);

                return;
            }

            collect($matches)->each(function ($match) {
                $level = match (strtolower(trim($match[2]))) {
                    'error' => 'error',
                    'warn' => 'warning',
                    default => 'notice',
                };

                $file = trim($match[3]);
                $line = trim($match[4]);
                $message = trim($match[5]);

                $this->output->writeln("::{$level} file={$file},line={$line}::{$message}");
            });
        } else {
            parent::onOutput($type, $buffer);

            return;
        }
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/lint/
     */
    protected function onError(Process $process): void
    {
        if ($process->getExitCode() >= 2) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithErrorsException();
        } else {
            if (! $this->option('ignore-warnings')) {
                throw new \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithWarningsException();
            }
        }
    }
}
