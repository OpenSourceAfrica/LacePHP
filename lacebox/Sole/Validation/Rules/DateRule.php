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

class DateRule implements RuleInterface
{
    private $field;

    public function setField(string $field): void { $this->field = $field; }

    public function validate($value, array $all): bool
    {
        if ($value === null || $value === '') return true;

        if ($value instanceof \DateTimeInterface) return true;

        if (!is_string($value) && !is_int($value)) return false;

        $ts = is_int($value) ? $value : strtotime((string)$value);
        return $ts !== false;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must be a valid date.";
    }
}