<?php

namespace Smakecloud\Skeema\Exceptions;

class RunningGhostMigrationsException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Found running gh-ost migrations');
    }

    public function getExitCode(): int
    {
        return 11;
    }
}
