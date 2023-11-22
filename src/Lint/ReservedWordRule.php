<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-reserved-word
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-reserved-word
 */
class ReservedWordRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-reserved-word';
    }
}
