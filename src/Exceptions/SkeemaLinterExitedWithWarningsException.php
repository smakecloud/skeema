<?php

namespace Smakecloud\Skeema\Exceptions;

class SkeemaLinterExitedWithWarningsException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Skeema linter exited with warnings. See output above for details.');
    }

    public function getExitCode(): int
    {
        return 3;
    }
}
