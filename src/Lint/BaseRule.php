<?php

namespace Smakecloud\Skeema\Lint;

abstract class BaseRule
{
    abstract public function getOptionString(): string;

    public function since(): string
    {
        return '1.9.0';
    }
}
