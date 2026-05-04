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

class BetweenRule implements RuleInterface
{
    /** @var float|int */
    protected $min;

    /** @var float|int */
    protected $max;

    /** @var string|null */
    protected $field;

    /**
     * Accepts "1,10" or "1 , 10"
     */
    public function __construct($paramStr)
    {
        $paramStr = (string)$paramStr;
        $parts = array_map('trim', explode(',', $paramStr));

        $a = $parts[0] ?? null;
        $b = $parts[1] ?? null;

        if (!is_numeric($a) || !is_numeric($b)) {
            throw new \InvalidArgumentException("between[] expects two numeric values: between[min,max]");
        }

        $a = $a + 0;
        $b = $b + 0;

        // normalise order
        $this->min = min($a, $b);
        $this->max = max($a, $b);
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function validate($value, array $all): bool
    {
        // Empty passes; required handles empties
        if ($value === null || $value === '') {
            return true;
        }

        // Array: compare count
        if (is_array($value)) {
            $n = count($value);
            return $n >= $this->min && $n <= $this->max;
        }

        // Numeric or numeric-string: numeric compare
        if (is_numeric($value)) {
            $n = (float)$value;
            return $n >= (float)$this->min && $n <= (float)$this->max;
        }

        // String: compare length
        $len = mb_strlen((string)$value);
        return $len >= (int)$this->min && $len <= (int)$this->max;
    }

    public function message(): string
    {
        return "Value must be between {$this->min} and {$this->max}.";
    }
}