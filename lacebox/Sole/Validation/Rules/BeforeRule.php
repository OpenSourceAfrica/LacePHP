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

class BeforeRule implements RuleInterface
{
    private $ref;
    private $field;

    public function __construct(string $ref = '')
    {
        $this->ref = trim($ref);
    }

    public function setField(string $field): void { $this->field = $field; }

    public function validate($value, array $all): bool
    {
        if ($value === null || $value === '') return true;

        $a = $this->toTs($value);
        if ($a === null) return false;

        $refVal = $this->refValue($all);
        $b = $this->toTs($refVal);
        if ($b === null) return false;

        return $a < $b;
    }

    private function refValue(array $all)
    {
        if ($this->ref !== '' && array_key_exists($this->ref, $all)) {
            return $all[$this->ref];
        }
        return $this->ref;
    }

    private function toTs($v): ?int
    {
        if ($v instanceof \DateTimeInterface) return $v->getTimestamp();
        if (is_int($v)) return $v;
        if (!is_string($v)) return null;

        $ts = strtotime($v);
        return ($ts === false) ? null : $ts;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must be a date before {$this->ref}.";
    }
}