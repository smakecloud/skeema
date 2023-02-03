<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-name-case
 * Commands	diff, push, lint, Cloud Linter
 * Default	ignore
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-name-case
 */
class NameCaseRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-name-case';
    }
}
