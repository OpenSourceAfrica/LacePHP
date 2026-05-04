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

class DifferentRule implements RuleInterface
{
    private $other;
    private $field;

    public function __construct(string $other = '')
    {
        $this->other = trim($other);
    }

    public function setField(string $field): void { $this->field = $field; }

    public function validate($value, array $all): bool
    {
        if ($value === null || $value === '') return true;
        $otherVal = $all[$this->other] ?? null;
        return (string)$value !== (string)$otherVal;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must be different from {$this->other}.";
    }
}