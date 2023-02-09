<?php

namespace Smakecloud\Skeema\Exceptions;

/**
 * @codeCoverageIgnore
 */
class SkeemaPushCouldNotUpdateTableException extends ExceptionWithExitCode
{
    public function __construct()
    {
        parent::__construct('Skeema push exited with errors. At least one table could not be updated due to use of unsupported features. See output above for details.');
    }

    public function getExitCode(): int
    {
        return 7;
    }
}
