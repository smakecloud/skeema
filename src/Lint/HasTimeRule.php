<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-has-time
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-has-time
 */
class HasTimeRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-has-time';
    }
}
