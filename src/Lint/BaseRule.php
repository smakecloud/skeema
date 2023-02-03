<?php

namespace Smakecloud\Skeema\Lint;

abstract class BaseRule {
    abstract public function getOptionString(): string;
}
