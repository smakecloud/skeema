<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-display-witdh
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-display-witdh
 */
class DisplayWidthRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-display-width';
    }
}
