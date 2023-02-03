<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;

class SkeemaLintCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:lint {--connection=}';

    protected $description = 'Lint the database schema ';

    public function getCommand(Connection $connection): string
    {
        return $this->getSkeemaCommand('lint ' . static::SKEEMA_ENV_NAME, $this->lintRules());
    }

    private function lintRules()
    {
        return collect($this->getConfig('skeema.lint.rules', []))
            ->mapWithKeys(function ($value, $key) {
                $option = $this->laravel->make($key)->getOptionString();

                return [$option => $value];
            })->toArray();
    }


}
