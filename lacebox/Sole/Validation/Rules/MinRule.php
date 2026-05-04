<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.lacephp.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;

class MinRule implements RuleInterface
{
    /** @var float|int */
    protected $min;

    /** @var string|null */
    protected $field;

    public function __construct($min)
    {
        if ($min === null || $min === '') {
            $min = 0;
        }

        if (!is_numeric($min)) {
            throw new \InvalidArgumentException("min[] expects a numeric value.");
        }

        $this->min = $min + 0;
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function validate($value, array $all): bool
    {
        // Empty values pass here; "required" handles empties.
        if ($value === null || $value === '') {
            return true;
        }

        // Arrays: min count
        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        // Numeric strings should behave like numbers
        if (is_numeric($value)) {
            return ((float)$value) >= (float)$this->min;
        }

        // Strings: min length
        $str = (string)$value;
        return mb_strlen($str) >= (int)$this->min;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must be at least {$this->stripDec($this->min)}.";
    }

    private function stripDec($n): string
    {
        return (floor($n) == $n) ? (string)(int)$n : (string)$n;
    }
}