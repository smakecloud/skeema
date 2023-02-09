<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaLintCommand
 * Runs the skeema lint command to lint the schema files against the database.
 */
class SkeemaLintCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:lint'
        . ' {--skip-format : Skip formatting the schema files}'
        . ' {--strip-definer= : Remove DEFINER clauses from *.sql files}'
        . ' {--strip-partitioning : Remove PARTITION BY clauses from *.sql files}'
        . ' {--allow-auto-inc= : List of allowed auto_increment column data types for lint-auto-inc}'
        . ' {--allow-charset= :	List of allowed character sets for lint-charset}'
        . ' {--allow-compression= : List of allowed compression settings for lint-compression}'
        . ' {--allow-definer= : List of allowed routine definers for lint-definer}'
        . ' {--allow-engine= : List of allowed storage engines for lint-engine}'
        . ' {--update-views : Reformat views in canonical single-line form}'
        . ' {--ignore-warnings : Exit with status 0 even if warnings are found}'
        . ' {--output-format=default : Output format (default, github, or quiet)}'
        . ' {--connection=}';

    protected $description = 'Lint the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('lint '.static::SKEEMA_ENV_NAME, $this->makeArgs());
    }

    private function makeArgs(): array
    {
        $args = [];

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
            ...$args
        ];
    }

    /**
     * Get the lint rules.
     */
    private function lintRules()
    {
        return collect($this->getConfig('skeema.lint.rules', []))
            ->mapWithKeys(function ($value, $key) {
                $option = $this->laravel->make($key)->getOptionString();

                return [$option => $value];
            })->toArray();
    }

    protected function onOutput($type, $buffer)
    {
        if($this->option('output-format') === 'quiet') {
            return;
        } elseif($this->option('output-format') === 'github') {
            $re = '/^(\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d) \[([A-Z]*)\]\w?(.*\.sql):(\d*):(.*)$/m';

            preg_match_all($re, $buffer, $matches, PREG_SET_ORDER, 0);

            if (blank($matches)) {
                return parent::onOutput($type, $buffer);
            }

            collect($matches)->each(function ($match) {
                $level = match(strtolower(trim($match[2]))) {
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
            return parent::onOutput($type, $buffer);
        }
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/lint/
     */
    protected function onError(Process $process)
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
