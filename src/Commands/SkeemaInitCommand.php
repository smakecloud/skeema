<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaInitCommand
 * Runs the skeema init command to create the schema files.
 * Patches the generated config file with environment variables interpolated.
 */
class SkeemaInitCommand extends SkeemaBaseCommand
{
    protected $signature = 'skeema:init {--force} {--connection=}';

    protected $description = 'Inits the database schema ';

    public function getCommand(Connection $connection): string
    {
        $this->confirmToProceed('Running skeema init will overwrite any existing schema files.');

        return $this->getSkeemaCommand('init '.static::SKEEMA_ENV_NAME, [
            'host' => $connection->getConfig('host'),
            'schema' => $connection->getConfig('database'),
            'user' => $connection->getConfig('username'),
            'password' => $connection->getConfig('password') === '' ? false : $connection->getConfig('password'),
            'dir' => '.',
        ], false);
    }

    protected function onSuccess(Process $process): void
    {
        $this->info('Skeema init successful');

        $this->patchSkeemaConfigFile();
    }

    /**
     * Patch config file with environment variables interpolated
     */
    private function getSkeemaConfig($flavor = 'mysql:5.7'): \Illuminate\Support\Stringable
    {
        return Str::of('generator=skeema:'.$this->getSkeemaVersion().PHP_EOL)
            ->append('['.static::SKEEMA_ENV_NAME.']'.PHP_EOL)
            ->append('flavor='.$flavor.PHP_EOL)
            ->append('host=$LARAVEL_SKEEMA_DB_HOST'.PHP_EOL)
            ->append('port=$LARAVEL_SKEEMA_DB_PORT'.PHP_EOL)
            ->append('schema=$LARAVEL_SKEEMA_DB_SCHEMA'.PHP_EOL)
            ->append('user=$LARAVEL_SKEEMA_DB_USER'.PHP_EOL)
            ->append('password=$LARAVEL_SKEEMA_DB_PASSWORD'.PHP_EOL);
    }

    /**
     * Get the value of a key from a Skeema config file
     */
    private function getSkeemaConfigValue($filePath, $key)
    {
        // Check if the file exists
        if (!file_exists($filePath)) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException($filePath);
        }

        // Read the file content
        $fileContent = file_get_contents($filePath);

        // Split the content into lines
        $lines = explode("\n", $fileContent);

        // Variable to hold the configuration
        $config = [];

        // Parse each line
        foreach ($lines as $line) {
            // Check if the line is a key-value pair
            if (strpos($line, '=') !== false) {
                list($configKey, $configValue) = explode('=', $line, 2);
                $config[trim($configKey)] = trim($configValue);
            }
        }

        // Check if the key exists in the configuration
        if (!array_key_exists($key, $config)) {
            return null;
        }

        return $config[$key];
    }

    /**
     * Patch config file with environment variables interpolated
     */
    private function patchSkeemaConfigFile(): void
    {
        $configFilePath = $this->getSkeemaDir().DIRECTORY_SEPARATOR.'.skeema';

        if (! $this->files->exists($configFilePath)) {
            // @codeCoverageIgnoreStart
            throw new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException($configFilePath);
            // @codeCoverageIgnoreEnd
        }

        $flavor = $this->getSkeemaConfigValue($configFilePath, 'flavor');
        $this->files->put($configFilePath, $this->getSkeemaConfig($flavor)->toString());
    }
}
