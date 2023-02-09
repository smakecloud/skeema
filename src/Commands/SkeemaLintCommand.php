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
    protected $signature = 'skeema:lint {--ignore-warnings} {--connection=}';

    protected $description = 'Lint the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('lint '.static::SKEEMA_ENV_NAME, [
            ...$this->lintRules(),
            'skip-format' => true,
        ]);
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
