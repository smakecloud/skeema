<?php

namespace Smakecloud\Skeema\Traits;

trait SerializesArguments
{
    /**
     * Serialize the given arguments.
     *
     * @param  array<string, mixed>  $args
     */
    public function serializeArgs(array $args): string
    {
        return implode(
            ' ',
            collect($args)
                ->map([$this, 'serializeArgument'])
                ->toArray()
        );
    }

    /**
     * Serialize the given argument.
     *
     * @param  mixed  $value
     * @param  string  $key
     */
    public function serializeArgument($value, $key): string
    {
        return match (true) {
            $value === false => '',
            $value === true => "--{$key}",
            default => "--{$key}=".escapeshellarg($value),
        };
    }
}
