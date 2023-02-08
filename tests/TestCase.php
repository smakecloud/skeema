<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
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

        parent::setUp();

        $this->removeExistingSkeemaDir();

        // Code after application created.
    }

    protected function getSkeemaDir(): string
    {
        return self::applicationBasePath() . '/' . config('skeema.dir');
    }

    private function removeExistingSkeemaDir(): void
    {
        $skeemaDir = $this->getSkeemaDir();

        if ($this->app->files->exists($skeemaDir)) {
            $this->app->files->deleteDirectory($skeemaDir);
        }
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
}