<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
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

        $this->patchSkeemaConfigFile();
    }

    /**
     * Patch config file with environment variables interpolated
     */
    private function getSkeemaConfig()
    {
        return Str::of("generator=skeema:" . $this->getSkeemaVersion() . PHP_EOL)
            ->append("[" . static::SKEEMA_ENV_NAME . "]" . PHP_EOL)
            ->append("flavor=mysql:5.7" . PHP_EOL)
            ->append("host=\$LARAVEL_SKEEMA_DB_HOST" . PHP_EOL)
            ->append("port=\$LARAVEL_SKEEMA_DB_PORT" . PHP_EOL)
            ->append("schema=\$LARAVEL_SKEEMA_DB_SCHEMA" . PHP_EOL)
            ->append("user=\$LARAVEL_SKEEMA_DB_USER" . PHP_EOL)
            ->append("password=\$LARAVEL_SKEEMA_DB_PASSWORD" . PHP_EOL);
    }

    /**
     * Patch config file with environment variables interpolated
     */
    private function patchSkeemaConfigFile()
    {
        $configFilePath = $this->getSkeemaDir() . DIRECTORY_SEPARATOR . '.skeema';

        if (!$this->files->exists($configFilePath)) {
            $this->error('Skeema config file not found');

            return;
        }

        $this->files->put($configFilePath, $this->getSkeemaConfig()->toString());
    }

}
