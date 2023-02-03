<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;

class SkeemaPushCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:push {--connection=}';

    protected $description = 'Diff the database schema ';

    public function getCommand(Connection $connection): string
    {
        return $this->getSkeemaCommand('push ' . static::SKEEMA_ENV_NAME, [

        ]);
    }

}
