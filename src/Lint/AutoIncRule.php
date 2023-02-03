<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-auto-inc
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-auto-inc
 */
class AutoIncRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-auto-inc';
    }
}
