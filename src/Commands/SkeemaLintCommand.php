<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithErrorsException;
use Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithWarningsException;
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
        .' {--connection=}';

    protected $description = 'Lint the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('lint '.static::SKEEMA_ENV_NAME, $this->makeArgs());
    }

    /**
     * Get the lint arguments.
     *
     * @return array<string, mixed>
     */
    private function makeArgs(): array
    {
        $args = [];

        collect([
            'skip-format',
            'strip-partitioning',
            'update-views',
        ])->filter(fn (string $option) => $this->option($option))
            ->each(function (string $option) use (&$args): void {
                $args[$option] = true;
            });

        collect([
            'strip-definer',
            'allow-auto-inc',
            'allow-charset',
            'allow-compression',
            'allow-definer',
            'allow-engine',
        ])->filter(fn (string $option) => $this->option($option))
            ->each(function (string $option) use (&$args): void {
                $args[$option] = $this->option($option);
            });

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
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        if ($this->option('output-format') === 'github') {
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

            return;
        }

        parent::onOutput($type, $buffer);
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/lint/
     *
     * @throws \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithErrorsException
     * @throws \Smakecloud\Skeema\Exceptions\SkeemaLinterExitedWithWarningsException
     */
    protected function onError(Process $process): void
    {
        if ($process->getExitCode() > 1) {
            throw new SkeemaLinterExitedWithErrorsException();
        }

        if (! $this->option('ignore-warnings')) {
            throw new SkeemaLinterExitedWithWarningsException();
        }
    }
}
