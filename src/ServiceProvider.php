<?php

namespace Smakecloud\Skeema;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Smakecloud\Skeema\Commands\SkeemaDiffCommand;
use Smakecloud\Skeema\Commands\SkeemaInitCommand;
use Smakecloud\Skeema\Commands\SkeemaLintCommand;
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

        $this->app->bind('command.skeema:init', SkeemaInitCommand::class);
        $this->app->bind('command.skeema:pull', SkeemaPullCommand::class);
        $this->app->bind('command.skeema:push', SkeemaPushCommand::class);
        $this->app->bind('command.skeema:diff', SkeemaDiffCommand::class);
        $this->app->bind('command.skeema:lint', SkeemaLintCommand::class);

        $this->commands([
            'command.skeema:init',
            'command.skeema:pull',
            'command.skeema:push',
            'command.skeema:diff',
            'command.skeema:lint',
        ]);
    }
}
