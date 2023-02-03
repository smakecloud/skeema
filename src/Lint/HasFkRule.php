<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-has-fk
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-has-fk
 */
class HasFkRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-has-fk';
    }
}
