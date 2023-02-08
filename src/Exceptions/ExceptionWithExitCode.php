<?php

namespace Smakecloud\Skeema\Exceptions;

use Exception;

abstract class ExceptionWithExitCode extends Exception
{
    abstract public function getExitCode(): int;
}
