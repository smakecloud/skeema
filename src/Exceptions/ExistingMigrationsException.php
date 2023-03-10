<?php

namespace Smakecloud\Skeema\Exceptions;

class ExistingMigrationsException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Laravel migrations exist');
    }

    public function getExitCode(): int
    {
        return 9;
    }
}
