<?php

namespace Smakecloud\Skeema;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Smakecloud\Skeema\Commands\SkeemaDeploymentCheckCommand;
use Smakecloud\Skeema\Commands\SkeemaDiffCommand;
use Smakecloud\Skeema\Commands\SkeemaInitCommand;
use Smakecloud\Skeema\Commands\SkeemaLintCommand;
use Smakecloud\Skeema\Commands\SkeemaMigrateAndPullCommand;
use Smakecloud\Skeema\Commands\SkeemaPullCommand;
use Smakecloud\Skeema\Commands\SkeemaPushCommand;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/skeema.php' => $this->app->configPath('skeema.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/skeema.php', 'skeema');

        $commands = collect([
            'command.skeema:init' => SkeemaInitCommand::class,
            'command.skeema:pull' => SkeemaPullCommand::class,
            'command.skeema:push' => SkeemaPushCommand::class,
            'command.skeema:diff' => SkeemaDiffCommand::class,
            'command.skeema:lint' => SkeemaLintCommand::class,
            'command.skeema:deployment-check' => SkeemaDeploymentCheckCommand::class,
            'command.skeema:migrate-and-pull' => SkeemaMigrateAndPullCommand::class,
        ])->each(function ($class, $key) {
            $this->app->singleton($key, $class);
        });

        $this->commands($commands->keys()->toArray());
    }

}
