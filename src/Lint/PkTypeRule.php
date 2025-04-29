<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-pk-type
 * Commands	diff, push, lint, Cloud Linter
 * Default	"ignore"
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-pk
 */
class PkTypeRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-pk-type';
    }

    public function since(): string
    {
        return '1.10.0';
    }
}
