<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-definer
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-definer
 */
class DefinerRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-definer';
    }
}
