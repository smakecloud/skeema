<?php

return [
    /*
     * The path to the skeema binary.
     */
    'bin' => env('SKEEMA_BIN', 'skeema'),

    /*
     * The directory where the schema files will be stored.
     */
    'dir' => env('SKEEMA_DIR_PATH', 'database/skeema'),

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
         * Any table smaller than this size (in bytes) will ignore the alter-wrapper option.
         * This permits skipping the overhead of external OSC tools when altering small tables.
         */
        'min_size' => '1m',

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
            /**
             * Skeema Community Version compatible Rules
             */
            \Smakecloud\Skeema\Lint\AutoIncRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\CharsetRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\CompressionRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\DefinerRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\DisplayWidthRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\DupeIndexRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\EngineRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasEnumRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasFkRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasFloatRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasRoutineRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasTimeRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\NameCaseRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\PkRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\ReservedWordRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\ZeroDateRule::class => 'ignore',
            /**
             * Skeema Plus/Max https://www.skeema.io/download/
             */
            \Smakecloud\Skeema\Lint\HasTriggerRule::class => 'ignore',
            \Smakecloud\Skeema\Lint\HasViewRule::class => 'ignore',
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
