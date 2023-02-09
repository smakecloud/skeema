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
        . ' {--update-views : Reformat views in canonical single-line form}'
        . ' {--ignore-warnings : Exit with status 0 even if warnings are found}'
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
