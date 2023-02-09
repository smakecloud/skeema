# Laravel Skeema

[![phpunit](https://github.com/smakecloud/skeema/actions/workflows/phpunit.yml/badge.svg)](https://github.com/smakecloud/skeema/actions/workflows/phpunit.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/smakecloud/skeema.svg?style=flat-square)](https://packagist.org/packages/smakecloud/skeema)
[![Total Downloads](https://img.shields.io/packagist/dt/smakecloud/skeema.svg?style=flat-square)](https://packagist.org/packages/smakecloud/skeema)

This package provides a Laravel wrapper around the [Skeema](https://www.skeema.io/) tool.

Skeema is a tool for managing MySQL database schemas.
It allows you to define your database schema in simple SQL files,
and then use Skeema to keep your database schema in sync.

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
            '--throttle-control-replicas='.env('DB_REPLICAS'),
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
         * Set to false to disable linting for diff
         * See https://www.skeema.io/docs/commands/diff
         */
        'diff' => [
            // \Smakecloud\Skeema\Lint\ZeroDateRule::class => 'error',
        ],

        /**
         * Linting rules for push
         * Set to false to disable linting for push
         * See https://www.skeema.io/docs/commands/push
         */
        'push' => [
            // \Smakecloud\Skeema\Lint\ZeroDateRule::class => 'error',
        ],
    ],
];
```

## Usage

### Dumping the schema

Run this once against your production database to generate the initial schema files.

```shell
$ php artisan skeema:init
```

```
Description:
  Inits the database schema

Usage:
  skeema:init [options]

Options:
      --force
      --connection[=CONNECTION]
  -h, --help                     Display help for the given command. When no command is given display help for the list command
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi|--no-ansi           Force (or disable --no-ansi) ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --env[=ENV]                The environment the command should run under
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Linting the schema

Lint the schema files with your configured rules.

```shell
$ php artisan skeema:lint
```

```
Description:
  Lint the database schema

Usage:
  skeema:lint [options]

Options:
      --skip-format                    Skip formatting the schema files
      --strip-definer[=STRIP-DEFINER]  Remove DEFINER clauses from *.sql files
      --strip-partitioning             Remove PARTITION BY clauses from *.sql files
      --update-views                   Reformat views in canonical single-line form
      --ignore-warnings                Exit with status 0 even if warnings are found
      --connection[=CONNECTION]
  -h, --help                           Display help for the given command. When no command is given display help for the list command
  -q, --quiet                          Do not output any message
  -V, --version                        Display this application version
      --ansi|--no-ansi                 Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                 Do not ask any interactive question
      --env[=ENV]                      The environment the command should run under
  -v|vv|vvv, --verbose                 Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Diffing the schema

Diff the schema files against the database.

```shell
$ php artisan skeema:diff
```

```
Description:
  Diff the database schema

Usage:
  skeema:diff [options]

Options:
      --ignore-warnings                        No error will be thrown if there are warnings
      --alter-algorithm[=ALTER-ALGORITHM]      The algorithm to use for ALTER TABLE statements
      --alter-lock[=ALTER-LOCK]                The lock to use for ALTER TABLE statements
      --alter-validate-virtual                 Apply a WITH VALIDATION clause to ALTER TABLEs affecting virtual columns
      --compare-metadata                       For stored programs, detect changes to creation-time sql_mode or DB collation
      --exact-match                            Follow *.sql table definitions exactly, even for differences with no functional impact
      --partitioning[=PARTITIONING]            Specify handling of partitioning status on the database side
      --strip-definer[=STRIP-DEFINER]          Ignore DEFINER clauses when comparing procs, funcs, views, or triggers
      --allow-auto-inc[=ALLOW-AUTO-INC]        List of allowed auto_increment column data types for lint-auto-inc
      --allow-charset[=ALLOW-CHARSET]          List of allowed character sets for lint-charset
      --allow-compression[=ALLOW-COMPRESSION]  List of allowed compression settings for lint-compression
      --allow-definer[=ALLOW-DEFINER]          List of allowed routine definers for lint-definer
      --allow-engine[=ALLOW-ENGINE]            List of allowed storage engines for lint-engine
      --allow-unsafe                           Permit generating ALTER or DROP operations that are potentially destructive
      --safe-below-size[=SAFE-BELOW-SIZE]      Always permit generating destructive operations for tables below this size in bytes
      --skip-verify                            Skip Test all generated ALTER statements on temp schema to verify correctness
      --connection[=CONNECTION]
  -h, --help                                   Display help for the given command. When no command is given display help for the list command
  -q, --quiet                                  Do not output any message
  -V, --version                                Display this application version
      --ansi|--no-ansi                         Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                         Do not ask any interactive question
      --env[=ENV]                              The environment the command should run under
  -v|vv|vvv, --verbose                         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Pushing the schema

Push the schema files to the database.

```shell
$ php artisan skeema:push
```

```
Description:
  Push the database schema

Usage:
  skeema:push [options]

Options:
      --alter-algorithm[=ALTER-ALGORITHM]      Apply an ALGORITHM clause to all ALTER TABLEs
      --alter-lock[=ALTER-LOCK]                Apply a LOCK clause to all ALTER TABLEs
      --alter-validate-virtual                 Apply a WITH VALIDATION clause to ALTER TABLEs affecting virtual columns
      --compare-metadata                       For stored programs, detect changes to creation-time sql_mode or DB collation
      --exact-match                            Follow *.sql table definitions exactly, even for differences with no functional impact
      --partitioning[=PARTITIONING]            Specify handling of partitioning status on the database side
      --strip-definer[=STRIP-DEFINER]          Ignore DEFINER clauses when comparing procs, funcs, views, or triggers
      --allow-auto-inc[=ALLOW-AUTO-INC]        List of allowed auto_increment column data types for lint-auto-inc
      --allow-charset[=ALLOW-CHARSET]          List of allowed character sets for lint-charset
      --allow-compression[=ALLOW-COMPRESSION]  List of allowed compression settings for lint-compression
      --allow-definer[=ALLOW-DEFINER]          List of allowed routine definers for lint-definer
      --allow-engine[=ALLOW-ENGINE]            List of allowed storage engines for lint-engine
      --allow-unsafe                           Permit generating ALTER or DROP operations that are potentially destructive
      --safe-below-size[=SAFE-BELOW-SIZE]      Always permit generating destructive operations for tables below this size in bytes
      --skip-verify                            Skip Test all generated ALTER statements on temp schema to verify correctness
      --dry-run                                Output DDL but don’t run it; equivalent to skeema diff
      --foreign-key-checks                     Force the server to check referential integrity of any new foreign key
      --force
      --connection[=CONNECTION]
  -h, --help                                   Display help for the given command. When no command is given display help for the list command
  -q, --quiet                                  Do not output any message
  -V, --version                                Display this application version
      --ansi|--no-ansi                         Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                         Do not ask any interactive question
      --env[=ENV]                              The environment the command should run under
  -v|vv|vvv, --verbose                         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Pulling the schema

Pull the schema files from the database.

```shell
$ php artisan skeema:pull
```

```
Description:
  Pull the database schema

Usage:
  skeema:pull [options]

Options:
      --skip-format                    Skip Reformat SQL statements to match canonical SHOW CREATE
      --include-auto-inc               Include starting auto-inc values in new table files, and update in existing files
      --new-schemas                    Detect any new schemas and populate new dirs for them (enabled by default; disable with skip-new-schemas)
      --strip-definer[=STRIP-DEFINER]  Omit DEFINER clauses when writing procs, funcs, views, and triggers to filesystem
      --strip-partitioning             Omit PARTITION BY clause when writing partitioned tables to filesystem
      --update-views                   Update definitions of existing views, using canonical form
      --update-partitioning            Update PARTITION BY clauses in existing table files
      --connection[=CONNECTION]
  -h, --help                           Display help for the given command. When no command is given display help for the list command
  -q, --quiet                          Do not output any message
  -V, --version                        Display this application version
      --ansi|--no-ansi                 Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                 Do not ask any interactive question
      --env[=ENV]                      The environment the command should run under
  -v|vv|vvv, --verbose                 Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

## Testing

```shell
$ composer test
```

**With coverage**

```shell
$ composer test:coverage
```

## Disclaimer

This package is not affiliated with Skeema in any way.

**Read the documentation of Skeema before using this package !**

**We don't take any responsibility for any damage caused by this package.**

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Skeema](https://www.skeema.io/)
- [Daursu](https://github.com/Daursu)
- [Smake® IT GmbH](https://github.com/smakecloud)
