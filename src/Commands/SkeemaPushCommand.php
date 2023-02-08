<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;

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

        return $this->getSkeemaCommand('push ' . static::SKEEMA_ENV_NAME, [

        ]);
    }

}
