<?php
namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;

class EmailRule implements RuleInterface
{
    public function validate($value, array $all): bool
    {
        return filter_var((string)$value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function message(): string
    {
        return 'Must be a valid email address.';
    }
}