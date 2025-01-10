<?php

namespace Tests;

use Illuminate\Console\Parser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionObject;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\StringInput;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            'Smakecloud\Skeema\ServiceProvider',
        ];
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        // Code before application created.
        $this->createTest1TableMigrationFile();

        parent::setUp();

        // Code after application created.
        $this->removeExistingSkeemaDir();
    }

    protected function createTest1TableMigrationFile()
    {
        file_put_contents(
            $this->applicationBasePath().'/database/migrations/2022_10_26_132948_create_test1_table.php',
            $this->getStub('test1-php')
        );
    }

    protected function deleteTest1TableMigrationFile()
    {
        if ($this->app->files->exists($this->applicationBasePath().'/database/migrations/2022_10_26_132948_create_test1_table.php')) {
            $this->app->files->delete($this->applicationBasePath().'/database/migrations/2022_10_26_132948_create_test1_table.php');
        }
    }

    protected function getSkeemaDir(): string
    {
        return self::applicationBasePath().'/'.config('skeema.dir');
    }

    private function removeExistingSkeemaDir(): void
    {
        $skeemaDir = $this->getSkeemaDir();

        if ($this->app->files->exists($skeemaDir)) {
            $this->app->files->deleteDirectory($skeemaDir);
        }
    }

    protected function overwriteSkeemaFile(string $file, string $content): void
    {
        $this->app->files->put($this->getSkeemaDir().'/'.$file, $content);
    }

    protected function getStub(string $name): string
    {
        return file_get_contents(__DIR__.'/stubs/'.$name);
    }

    /**
     * Get Application base path.
     *
     * @return string
     */
    public static function applicationBasePath()
    {
        return __DIR__.'/laravel';
    }

    /**
     * Run test in 'prod' env
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function runsInProduction($app)
    {
        $app['env'] = 'production';
        $app->config->set('app.env', 'production');
    }

    /**
     * Execute the given command simulating the given input.
     * Returns the skeema cli arguments generated by the given cmd.
     */
    protected function getSkeemaArgs($class, $stringInput): array
    {
        if (substr($stringInput, 0, 1) != ' ') {
            $stringInput = ' '.$stringInput;
        }

        $cmd = app($class);
        $cmd->setLaravel($this->app);

        [$name, $arguments, $options] = Parser::parse($cmd->getSignature());

        $cmd->setName($name);

        $definition = new InputDefinition();
        $definition->addArguments($arguments);
        $definition->addOptions($options);
        $cmd->setDefinition($definition);

        $strInput = new StringInput($stringInput);
        $strInput->bind($definition);

        $cmd->setInput($strInput);

        $reflector = new ReflectionObject($cmd);

        $hasMakeArgs = $reflector->hasMethod('makeArgs');

        $this->assertTrue($hasMakeArgs, 'makeArgs method not found on class '.$class);

        $method = $reflector->getMethod('makeArgs');
        $method->setAccessible(true);

        return $method->invoke($cmd);
    }

    protected function getSkeemaVersionString(): string
    {
        exec('skeema version', $output, $returnVar);

        // Check if the command executed successfully
        if ($returnVar !== 0) {
            throw new \RuntimeException('Failed to execute skeema version command.');
        }

        // Extract the version number using regular expression
        $versionRegex = '/skeema version (\S+),/';
        if (preg_match($versionRegex, implode("\n", $output), $matches)) {
            return 'skeema:'.$matches[1];
        }

        throw new \RuntimeException('Unable to parse skeema version.');
    }
}
