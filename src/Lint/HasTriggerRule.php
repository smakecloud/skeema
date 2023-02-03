<?php

namespace Smakecloud\Skeema\Lint;

/**
 * Not available in the Community edition of Skeema!
 *
 * lint-has-trigger
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-has-trigger
 */
class HasTriggerRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-has-trigger';
    }
}
