<?php

namespace Smakecloud\Skeema\Exceptions;

class CommandCancelledException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Command cancelled.');
    }

    public function getExitCode(): int
    {
        return 1;
    }
}
