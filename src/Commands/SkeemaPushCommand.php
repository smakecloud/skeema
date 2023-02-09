<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaPushCommand
 * Runs the skeema push command to push the schema files into the database.
 */
class SkeemaPushCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:push {--force} {--connection=}';

    protected $description = 'Diff the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->confirmToProceed('Running skeema push in production.');
        $this->ensureSkeemaConfigFileExists();

        $args = [];

        if ($this->getConfig('skeema.alter_wrapper.enabled', false)) {
            $args['alter-wrapper'] = $this->getAlterWrapperCommand();
            $args['alter-wrapper-min-size'] = $this->getConfig(('skeema.alter_wrapper.min_size'), '0');
        }

        return $this->getSkeemaCommand('push '.static::SKEEMA_ENV_NAME, $args);
    }

    /**
     * Reference: https://www.skeema.io/docs/commands/lint/
     */
    protected function onError(Process $process)
    {
        if ($process->getExitCode() >= 2) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaPushFatalErrorException();
        } else {
            // @codeCoverageIgnoreStart
            throw new \Smakecloud\Skeema\Exceptions\SkeemaPushCouldNotUpdateTableException();
            // @codeCoverageIgnoreEnd
        }
    }
}
