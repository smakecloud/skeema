<?php

namespace Smakecloud\Skeema\Exceptions;

class ExistingDumpFileException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Laravel sql dumpfile exist');
    }

    public function getExitCode(): int
    {
        return 10;
    }
}
