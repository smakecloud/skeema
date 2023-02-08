# Laravel Skeema

[![phpunit](https://github.com/smakecloud/skeema/actions/workflows/phpunit.yml/badge.svg)](https://github.com/smakecloud/skeema/actions/workflows/phpunit.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/robinvdvleuten/laravel-skeema.svg?style=flat-square)](https://packagist.org/packages/robinvdvleuten/laravel-skeema)
[![Quality Score](https://img.shields.io/scrutinizer/g/robinvdvleuten/laravel-skeema.svg?style=flat-square)](https://scrutinizer-ci.com/g/robinvdvleuten/laravel-skeema)
[![Total Downloads](https://img.shields.io/packagist/dt/robinvdvleuten/laravel-skeema.svg?style=flat-square)](https://packagist.org/packages/robinvdvleuten/laravel-skeema)

This package provides a Laravel wrapper around the [Skeema](https://www.skeema.io/) tool.
Skeema is a tool for managing MySQL database schemas.
It allows you to define your database schema in simple SQL files,
and then use Skeema to keep your database schema in sync with that file.

## Installation

You can install the package via composer:

```bash
composer require smakecloud/skeema

php artisan vendor:publish --provider="SmakeCloud\Skeema\SkeemaServiceProvider"
```

## Configuration

``` php
<?php


return [
    /*
     * The path to the skeema binary.
     */
    'bin' => env('SKEEMA_BIN', 'skeema'),

    /*
     * The directory where the schema files will be stored.
     */
    'dir' => 'database/skeema',

    /*
     * The connection to use when dumping the schema.
     */
    'connection' => env('DB_CONNECTION', 'mysql'),

    /**
     * Alter Wrapper
     */
    'alter_wrapper' => [
        /*
         * Enable the alter wrapper.
         */
        'enabled' => env('SKEEMA_WRAPPER_ENABLED', false),

        /*
         * The path to the wrapper binary.
         */
        'bin' => env('SKEEMA_WRAPPER_BIN', 'gh-ost'),

        /**
         * Any table smaller than this size (in bytes) will ignore the alter-wrapper option. This permits skipping the overhead of external OSC tools when altering small tables.
         */
        'min_size' => '0',

        /**
         * https://github.com/github/gh-ost/blob/master/doc/command-line-flags.md
         */
        'params' => [
            '--max-load=Threads_running=25',
            '--critical-load=Threads_running=1000',
            '--chunk-size=1000',
            '--throttle-control-replicas=' . env('DB_REPLICAS'),
            '--max-lag-millis=1500',
            '--verbose',
            '--assume-rbr',
            '--allow-on-master',
            '--cut-over=default',
            '--exact-rowcount',
            '--concurrent-rowcount',
            '--default-retries=120',
            '--timestamp-old-table',
            // https://github.com/github/gh-ost/blob/master/doc/command-line-flags.md#postpone-cut-over-flag-file
            '--postpone-cut-over-flag-file=/tmp/ghost.postpone.flag',
        ],
    ],

    /**
     * Linter specific config
     * lint, diff, push, Cloud Linter
     */
    'lint' => [
        /**
         * Linting rules for all supported cmds
         */
        'rules' => [
            \Smakecloud\Skeema\Lint\AutoIncRule::class => 'warning',
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'warning',
            \Smakecloud\Skeema\Lint\CompressionRule::class => 'warning',
            \Smakecloud\Skeema\Lint\DefinerRule::class => 'error',
            \Smakecloud\Skeema\Lint\DisplayWidthRule::class => 'warning',
            \Smakecloud\Skeema\Lint\DupeIndexRule::class => 'error',
            \Smakecloud\Skeema\Lint\EngineRule::class => 'warning',
            \Smakecloud\Skeema\Lint\HasEnumRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasFkRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasFloatRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasRoutineRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasTimeRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\NameCaseRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\PkRule::class => 'warning',
            \Smakecloud\Skeema\Lint\ZeroDateRule::class => 'warning',

            /**
             * These rules are disabled by default
             * because they are not available in the Community edition of Skeema
             *
             * https://www.skeema.io/download/
             */

            // \Smakecloud\Skeema\Lint\HasTriggerRule::class => 'error',
            // \Smakecloud\Skeema\Lint\HasViewRule::class => 'error',
        ],

        /**
         * Linting rules for diff
         * Sett to false to disable linting for diff
         * See https://www.skeema.io/docs/commands/diff
         */
        'diff' => [
            // \Smakecloud\Skeema\Lint\ZeroDateRule::class => 'error',
        ],

        /**
         * Linting rules for push
         * Sett to false to disable linting for push
         * See https://www.skeema.io/docs/commands/push
         */
        'push' => [
            // \Smakecloud\Skeema\Lint\ZeroDateRule::class => 'error',
        ],
    ]
];
```

## Usage

### Dumping the schema

Run this once against your production database to generate the initial schema files.

```bash
php artisan skeema:init
```

### Linting the schema

Lint the schema files with your configured rules.

```bash
php artisan skeema:lint
```

### Diffing the schema

Diff the schema files against the database.

```bash
php artisan skeema:diff
```

### Pushing the schema

Push the schema files to the database.

```bash
php artisan skeema:push
```

### Pulling the schema

Pull the schema files from the database.

```bash
php artisan skeema:pull
```


### Cloud Linter

Lint the schema files with your configured rules in CI.
See https://www.skeema.io/docs/cloud-linter

```bash
php artisan skeema:cloud-linter
```

## Testing

``` bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Skeema](https://www.skeema.io/)
- [Daursu](https://github.com/Daursu)
- [Smakecloud](https://github.com/smakecloud)
