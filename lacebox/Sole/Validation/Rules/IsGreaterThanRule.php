<?php
namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;

class IsGreaterThanRule implements RuleInterface
{
    protected $min;

    public function __construct($min)
    {
        $this->min = $min;
    }

    public function validate($value, array $allData): bool
    {
        if (! is_numeric($value)) {
            return false;
        }
        return ((float)$value) > $this->min;
    }

    public function message(): string
    {
        return "The value must be greater than {$this->min}.";
    }
}
