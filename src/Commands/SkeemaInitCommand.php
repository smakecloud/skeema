<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Symfony\Component\Process\Process;

class SkeemaInitCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:init {--connection=}';

    protected $description = 'Inits the database schema ';

    public function getCommand(Connection $connection): string
    {
        return $this->getSkeemaCommand('init ' . static::SKEEMA_ENV_NAME, [
            'host' => $connection->getConfig('host'),
            'schema' => $connection->getConfig('database'),
            'user' => $connection->getConfig('username'),
            'password' => $connection->getConfig('password') === '' ? false : $connection->getConfig('password'),
            'dir' => '.',
        ], false);
    }

    protected function onSuccess(Process $process)
    {
        $this->info('Skeema init successful');
    }

}
