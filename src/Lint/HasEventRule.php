<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-has-event
 * Commands	diff, push, lint, Cloud Linter
 * Default	"ignore"
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 * Not available in the Community edition of Skeema
 *
 * Docs: https://www.skeema.io/docs/options/#lint-has-event
 */
class HasEventRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-has-event';
    }

    public function since(): string
    {
        return '1.12.0';
    }
}
