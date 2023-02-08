<?php

namespace Smakecloud\Skeema\Exceptions;

class SkeemaDiffExitedWithErrorsException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Skeema diff exited with errors. See output above for details.');
    }

    public function getExitCode(): int
    {
        return 6;
    }
}
