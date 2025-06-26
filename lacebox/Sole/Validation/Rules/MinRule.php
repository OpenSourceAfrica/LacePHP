<?php
namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;

class MinRule implements RuleInterface
{
    protected $min;

    public function __construct(int $min)
    {
        $this->min = $min;
    }

    public function validate($value, array $all): bool
    {
        return is_string($value) && mb_strlen($value) >= $this->min;
    }

    public function message(): string
    {
        return "Minimum length is {$this->min} characters.";
    }
}