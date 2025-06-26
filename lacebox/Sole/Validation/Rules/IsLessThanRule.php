<?php
namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;

class IsEqualsToRule implements RuleInterface
{
    protected $target;

    public function __construct($target)
    {
        $this->target = $target;
    }

    public function validate($value, array $allData): bool
    {
        // strict comparison if both scalar, loose otherwise
        return $value == $this->target;
    }

    public function message(): string
    {
        return "The value must be equal to {$this->target}.";
    }
}