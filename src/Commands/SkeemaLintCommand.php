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
        $args = collect([
            'temp-schema' => $this->option('temp-schema') ?: $this->getTempSchemaName(),
            'temp-schema-threads' => ($this->option('temp-schema-threads') && is_numeric($this->option('temp-schema-threads'))) ? $this->option('temp-schema-threads') : null,
            'temp-schema-binlog' => $this->option('temp-schema-binlog'),
            'skip-format' => $this->option('skip-format') ? true : null,
            'include-auto-inc' => $this->option('include-auto-inc') ? true : null,
            'new-schemas' => $this->option('new-schemas') ? true : null,
            'strip-definer' => $this->option('strip-definer'),
            'strip-partitioning' => $this->option('strip-partitioning') ? true : null,
            'update-views' => $this->option('update-views') ? true : null,
            'allow-auto-inc' => $this->option('allow-auto-inc'),
            'allow-charset' => $this->option('allow-charset'),
            'allow-compression' => $this->option('allow-compression'),
            'allow-definer' => $this->option('allow-definer'),
            'allow-engine' => $this->option('allow-engine'),
        ])->filter()
        ->toArray();

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

    protected function onOutput($type, $buffer): void
    {
        if ($this->option('output-format') === 'quiet') {
            return;
        }

        if ($this->option('output-format') === 'github') {
            $re = '/^(\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d) \[([A-Z]*)\]\w?(.*\.sql):(\d*):(.*)$/m';

            preg_match_all($re, $buffer, $matches, PREG_SET_ORDER, 0);

            if (filled($matches)) {
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

                return;
            }
        }

        parent::onOutput($type, $buffer);
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/lint/
     */
    protected function onError(Process $process): void
    {
        if ($process->getExitCode() >= 2) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithErrorsException();
        }

        if (! $this->option('ignore-warnings')) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithWarningsException();
        }
    }
}
