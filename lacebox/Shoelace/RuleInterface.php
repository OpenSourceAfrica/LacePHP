<?php
namespace Lacebox\Shoelace;

interface RuleInterface
{
    /**
     * Validate the given value within the full payload.
     *
     * @param  mixed  $value
     * @param  array  $allData
     * @return bool
     */
    public function validate($value, array $allData): bool;

    /**
     * Return an error message when validation fails.
     *
     * @return string
     */
    public function message(): string;
}
