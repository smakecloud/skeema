<?php

namespace Smakecloud\Skeema\Exceptions;

class SkeemaDiffExitedWithWarningsException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Skeema diff exited with warnings. See output above for details.');
    }

    public function getExitCode(): int
    {
        return 5;
    }
}
