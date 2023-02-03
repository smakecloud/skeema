<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-zero-date
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-zero-date
 */
class ZeroDateRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-zero-date';
    }
}
