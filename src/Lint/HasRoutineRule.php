<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-has-routine
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-has-routine
 */
class HasRoutineRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-has-routine';
    }
}
