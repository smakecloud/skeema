<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;

/**
 * Class SkeemaLintCommand
 * Runs the skeema lint command to lint the schema files against the database.
 */
class SkeemaLintCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:lint {--connection=}';

    protected $description = 'Lint the database schema ';

    public function getCommand(Connection $connection): string
    {
        return $this->getSkeemaCommand('lint ' . static::SKEEMA_ENV_NAME, $this->lintRules());
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


}
