<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;

class SkeemaPullCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:pull {--connection=}';

    protected $description = 'Pull the database schema ';

    public function getCommand(Connection $connection): string
    {
        return $this->getSkeemaCommand('pull ' . static::SKEEMA_ENV_NAME, [

        ]);
    }

}
