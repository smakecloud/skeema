<?php

namespace Smakecloud\Skeema\Lint;

/**
 * lint-dupe-index
 * Commands	diff, push, lint, Cloud Linter
 * Default	“warning”
 * Type	enum
 * Restrictions	Requires one of these values: “ignore”, “warning”, “error”
 *
 * Docs: https://www.skeema.io/docs/options/#lint-dupe-index
 */
class DupeIndexRule extends BaseRule
{
    public function getOptionString(): string
    {
        return 'lint-dupe-index';
    }
}
