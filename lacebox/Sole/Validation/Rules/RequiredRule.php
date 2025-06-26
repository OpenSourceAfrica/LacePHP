<?php
namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;

class RequiredRule implements RuleInterface
{
    public function validate($value, array $all): bool
    {
        return !is_null($value) && $value !== '';
    }

    public function message(): string
    {
        return 'This field is required.';
    }
}
