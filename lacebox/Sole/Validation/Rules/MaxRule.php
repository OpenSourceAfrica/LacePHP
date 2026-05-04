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

class MaxRule implements RuleInterface
{
    /** @var float|int */
    protected $max;

    /** @var string|null */
    protected $field;

    /**
     * Accept int, float, or numeric string coming from RequestValidator.
     */
    public function __construct($max)
    {
        if ($max === null || $max === '') {
            $max = 0;
        }

        if (!is_numeric($max)) {
            throw new \InvalidArgumentException("max[] expects a numeric value.");
        }

        // store as number
        $this->max = $max + 0;
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

        // Arrays: max count
        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        // Numeric strings should behave like numbers
        if (is_numeric($value)) {
            return ((float)$value) <= (float)$this->max;
        }

        // Strings: max length (multibyte-safe)
        $str = (string)$value;
        return mb_strlen($str) <= (int)$this->max;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must not be greater than {$this->stripDec($this->max)}.";
    }

    private function stripDec($n): string
    {
        return (floor($n) == $n) ? (string)(int)$n : (string)$n;
    }
}