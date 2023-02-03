<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;

class SkeemaDiffCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:diff {--connection=}';

    protected $description = 'Diff the database schema ';

    public function getCommand(Connection $connection): string
    {
        return $this->getSkeemaCommand('diff ' . static::SKEEMA_ENV_NAME, [

        ]);
    }

}
