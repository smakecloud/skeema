<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Smakecloud\Skeema\Traits\SerializesArguments;
use Symfony\Component\Process\Process;

abstract class SkeemaBaseCommand extends Command
{
    use SerializesArguments;

    public const SKEEMA_ENV_NAME = 'laravel';
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The process factory callback.
     *
     * @var callable
     */
    protected $processFactory;

    /**
     * Get the cmd to run.
     *
     * @return string
     */
    abstract protected function getCommand(Connection $connection): string;

    public function handle()
    {
        $this->files = $this->laravel->get(Filesystem::class);

        $this->processFactory = function (...$arguments) {
            return Process::fromShellCommandline(...$arguments)
                ->setIdleTimeout(null)
                ->setTimeout(null);
        };

        $this->ensureSkeemaDirExists();

        $this->runProcess($this->getCommand($this->getConnection()));
    }

    /**
     * Get the path to the skeema configuration file.
     *
     * @return string
     */
    protected function getSkeemaDir()
    {
        return $this->laravel->basePath(
            $this->getConfig('skeema.dir', 'database' . DIRECTORY_SEPARATOR . 'skeema')
        );
    }

    /**
     * Get the config.
     *
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        if (Str::startsWith($key, 'skeema.')) {
            $replacedString = Str::of($key)
                ->replaceFirst('skeema.', '')
                ->toString();

            if ($this->hasOption($replacedString)) {
                return $this->option($replacedString);
            }
        }

        return $this->laravel->get('config')->get($key, $default);
    }


    /**
     * Get the connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection(): Connection
    {
        $connectionName = $this->getConfig('skeema.connection', 'mysql');

        return $this->laravel->get('db')->connection($connectionName);
    }

    /**
     * Get the Skeema version.
     *
     * @return string
     */
    protected function getSkeemaVersion(): string
    {
        $process = call_user_func(
            $this->processFactory,
            'skeema --version',
            $this->getSkeemaDir(),
            $this->getProcessEnvironment()
        );

        $process->run();

        return Str::of(Str::of($process->getOutput())
            ->explode(' ')
            ->get(2)
        )->beforeLast(',')->toString();
    }

    /**
     * Ensure the skeema directory exists.
     *
     * @return void
     */
    protected function ensureSkeemaDirExists()
    {
        if (!$this->files->exists($this->getSkeemaDir())) {
            $this->files->makeDirectory($this->getSkeemaDir(), 0755, true);
        }
    }

    /**
     * Run the process.
     *
     * @param  string  $command
     * @return void
     */
    protected function runProcess($command)
    {
        $process = call_user_func(
            $this->processFactory,
            $command,
            $this->getSkeemaDir(),
            $this->getProcessEnvironment()
        );

        $process->run(fn ($type, $line) => $this->onOutput($type, $line));

        if ($process->isSuccessful()) {
            $this->onSuccess($process);

            return;
        }

        $this->onError($process);
    }

    /**
     * Gets the environment variables to pass to the Skeema process.
     *
     * @return array
     */
    protected function getProcessEnvironment()
    {
        return [
            'LARAVEL_SKEEMA_DB_HOST' => $this->getConnection()->getConfig('host'),
            'LARAVEL_SKEEMA_DB_PORT' => $this->getConnection()->getConfig('port'),
            'LARAVEL_SKEEMA_DB_USER' => $this->getConnection()->getConfig('username'),
            'LARAVEL_SKEEMA_DB_PASSWORD' => $this->getConnection()->getConfig('password'),
            'LARAVEL_SKEEMA_DB_SCHEMA' => $this->getConnection()->getConfig('database'),
        ];
    }

    /**
     * Handle the process output.
     *
     * @param  int  $type
     * @param  string  $buffer
     * @return void
     */
    protected function onOutput($type, $buffer)
    {
        $re = '/^(\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d) \[([A-Z]*)\] (.*)$/m';
        preg_match_all($re, $buffer, $matches, PREG_SET_ORDER, 0);

        if (blank($matches)) {
            $this->info($buffer);

            return;
        }

        collect($matches)->each(function ($match) {
            $message = $match[3];

            $lowerLevel = Str::of($match[2])->lower()->toString();
            $upperLevel = Str::of($match[2])->upper()->toString();

            $this->{$lowerLevel}(Str::of('[' . $upperLevel . ']' . $message));
        });
    }

    /**
     * Called on successfull  execution.
     */
    protected function onSuccess(Process $process)
    {
        //$this->info($process->getOutput());
    }

    /**
     * Called on error.
     */
    protected function onError(Process $process)
    {
        //$this->error($process->getErrorOutput());
    }

    /**
     * Get the alter wrapper command.
     *
     * @return string
     */
    private function getAlterWrapperCommand()
    {
        return Str::of('gh-ost')
            ->append(' --execute')
            ->append(' --alter {CLAUSES}')
            ->append(' --schema={SCHEMA}')
            ->append(' --table={TABLE}')
            ->append(' --host={HOST}')
            ->append(' --user={USER}')
            ->append(' --password={PASSWORD}')
            ->append(' ' . implode(' ', $this->getConfig('skeema.alter_wrapper.params', [])))
            ->toString();
    }

    /**
     * Get the base arguments for the skeema command.
     *
     * @return array
     */
    protected function getBaseArgs(): array
    {
        return [
            'default-character-set' => $this->getConnection()->getConfig('charset'),
            'default-collation' => $this->getConnection()->getConfig('collation'),
            'alter-wrapper' => $this->getAlterWrapperCommand(),
            'alter-wrapper-min-size' => $this->getConfig(('skeema.alter_wrapper.min_size'), '0'),
        ];
    }

    /**
     * Get the skeema command.
     *
     * @param  string  $command
     * @param  array  $arguments
     * @return string
     */
    protected function getSkeemaCommand(string $command, array $arguments = [], bool $withBaseArgs = true): string
    {
        $command = Str::of($this->getConfig('skeema.bin', 'skeema'))
            ->append(' ' . $command)
            ->append(' ' . $this->serializeArgs($arguments));

        if ($withBaseArgs) {
            $command->append(' ' . $this->serializeArgs($this->getBaseArgs()));
        }

        return $command->toString();
    }
}
