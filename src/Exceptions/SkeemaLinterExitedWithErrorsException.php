<?php

namespace Smakecloud\Skeema\Exceptions;

class SkeemaLinterExitedWithErrorsException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Skeema linter exited with errors. See output above for details.');
    }

    public function getExitCode(): int
    {
        return 4;
    }
}
