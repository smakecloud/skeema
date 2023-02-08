<?php

namespace Smakecloud\Skeema\Exceptions;

class SkeemaPushFatalErrorException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Skeema push exited with fatal error. See output above for details.');
    }

    public function getExitCode(): int
    {
        return 8;
    }
}
