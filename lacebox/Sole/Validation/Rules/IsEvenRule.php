<?php
namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;

class IsEvenRule implements RuleInterface
{
    public function validate($value, array $allData): bool
    {
        // allow numeric strings too
        if (! is_numeric($value)) {
            return false;
        }
        return ((int)$value) % 2 === 0;
    }

    public function message(): string
    {
        return 'The value must be an even number.';
    }
}