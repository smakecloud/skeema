<?php

namespace Smakecloud\Skeema\Exceptions;

use Exception;

class SkeemaConfigNotFoundException extends ExceptionWithExitCode
{
    public function __construct(string $configFilePath)
    {
        parent::__construct("Skeema config file not found at {$configFilePath}");
    }

    public function getExitCode(): int
    {
        return 2;
    }
}
