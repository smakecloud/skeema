<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;

/**
 * Class SkeemaPullCommand
 * Runs the skeema pull command to pull the database schema into the schema files.
 */
class SkeemaPullCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:pull {--connection=}';

    protected $description = 'Pull the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->ensureSkeemaConfigFileExists();

        return $this->getSkeemaCommand('pull '.static::SKEEMA_ENV_NAME, [

        ]);
    }
}
