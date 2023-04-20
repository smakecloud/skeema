<?php

namespace Smakecloud\Skeema\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Smakecloud\Skeema\Traits\SerializesArguments;
use Symfony\Component\Process\Process;

/**
 * Class SkeemaBaseCommand.
 *
 * @TODO: Once we drop Laravel 9 support, we can refactor the process factory to use:
 * https://laravel-news.com/process-facade-laravel-10
 */
abstract class SkeemaBaseCommand extends Command
{
    use SerializesArguments;

    /**
     * The skeema environment name.
     * Used in config file overvwrites and command line arguments.
     */
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

    /**
     * Handle the command.
     */
    public function handle(): int
    {
        $exitCode = 0;

        $this->files = $this->laravel->get(Filesystem::class);

        $this->processFactory = function (...$arguments) {
            return Process::fromShellCommandline(...$arguments)
                ->setIdleTimeout(null)
                ->setTimeout(null);
        };

        $this->ensureSkeemaDirExists();

        try {
            $command = $this->getCommand($this->getConnection());

            $this->runProcess($command);
        } catch (\Smakecloud\Skeema\Exceptions\ExceptionWithExitCode $e) {
            $this->error($e->getMessage());

            $exitCode = $e->getExitCode();
        }
        // @codeCoverageIgnoreStart
        catch (\Exception $e) {
            $this->error($e->getMessage());

            $exitCode = -1;
        }
        // @codeCoverageIgnoreEnd

        return $exitCode;
    }

    /**
     * Get the path to the skeema configuration file.
     *
     * @return string
     */
    protected function getSkeemaDir()
    {
        return $this->laravel->basePath(
            $this->getConfig('skeema.dir', 'database'.DIRECTORY_SEPARATOR.'skeema')
        );
    }

    /**
     * Get the config.
     *
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null)
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
        if (! $this->files->exists($this->getSkeemaDir())) {
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
     * @return array<string, string>
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
            $message = trim($match[3]);

            $lowerLevel = Str::of($match[2])->lower()->toString();
            $upperLevel = Str::of($match[2])->upper()->toString();

            $this->{$lowerLevel}(Str::of('['.$upperLevel.'] '.$message));
        });
    }

    /**
     * Called on successfull  execution.
     */
    protected function onSuccess(Process $process): void
    {
        //$this->info($process->getOutput());
    }

    /**
     * Called on error.
     *
     * @codeCoverageIgnore
     */
    protected function onError(Process $process): void
    {
        //$this->error($process->getErrorOutput());
    }

    /**
     * Get the alter wrapper command.
     *
     * @return string
     */
    protected function getAlterWrapperCommand(): string
    {
        return Str::of($this->getConfig(('skeema.alter_wrapper.bin'), 'gh-ost'))
            ->append(' --execute')
            ->append(' --alter {CLAUSES}')
            ->append(' --database={SCHEMA}')
            ->append(' --table={TABLE}')
            ->append(' --host={HOST}')
            ->append(' --user={USER}')
            ->append(' --password={PASSWORDX}')
            ->append(' '.implode(' ', $this->getConfig('skeema.alter_wrapper.params', [])))
            ->toString();
    }

    /**
     * Get the base arguments for the skeema command.
     *
     * @return array<string, string>
     */
    protected function getBaseArgs(): array
    {
        $baseArgs = [
            // 'default-character-set' => $this->getConnection()->getConfig('charset'),
            // 'default-collation' => $this->getConnection()->getConfig('collation'),
        ];

        if ($this->getConnection()->getConfig('sslmode') === 'require') {
            $baseArgs['ssl-ca'] = $this->getConnection()->getConfig('sslrootcert');
            $baseArgs['ssl-cert'] = $this->getConnection()->getConfig('sslcert');
            $baseArgs['ssl-key'] = $this->getConnection()->getConfig('sslkey');
        }

        return $baseArgs;
    }

    /**
     * Get the skeema command.
     *
     * @param  string  $command
     * @param  array<string, mixed>  $arguments
     * @return string
     */
    protected function getSkeemaCommand(string $command, array $arguments = [], bool $withBaseArgs = true): string
    {
        $command = Str::of($this->getConfig('skeema.bin', 'skeema'))
            ->append(' '.$command)
            ->append(' '.$this->serializeArgs($arguments));

        if ($withBaseArgs) {
            $command = $command->append(' '.$this->serializeArgs($this->getBaseArgs()));
        }

        return $command->toString();
    }

    /**
     * Confirm or force if we are running in production.
     */
    protected function confirmToProceed(string $warning = 'Application In Production!'): void
    {
        if ($this->option('force')) {
            return;
        }

        if ($this->applicationIsRunningInProduction()) {
            if ($this->confirm($warning.' Proceed?', false)) {
                return;
            }

            throw new \Smakecloud\Skeema\Exceptions\CommandCancelledException();
        }
    }

    /**
     * Check if we are running in production
     */
    private function applicationIsRunningInProduction(): bool
    {
        return $this->laravel->environment() === 'production';
    }

    protected function ensureSkeemaConfigFileExists(): void
    {
        $configFilePath = $this->getSkeemaDir().DIRECTORY_SEPARATOR.'.skeema';

        if (! $this->files->exists($configFilePath)) {
            throw new \Smakecloud\Skeema\Exceptions\SkeemaConfigNotFoundException($configFilePath);
        }
    }

    public function getSignature(): string
    {
        return $this->signature;
    }
}
