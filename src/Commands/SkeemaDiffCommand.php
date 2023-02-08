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
    protected $signature = 'skeema:diff {--ignore-warnings} {--connection=}';

    protected $description = 'Diff the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('diff ' . static::SKEEMA_ENV_NAME, [

        ]);
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/diff/
     */
    protected function onError(Process $process)
    {
        if ($process->getExitCode() >= 2) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithErrorsException();
        } else {
            if(!$this->option('ignore-warnings')) {
                throw new \Smakecloud\Skeema\Exceptions\SkeemaDiffExitedWithWarningsException();
            }
        }
    }

}
