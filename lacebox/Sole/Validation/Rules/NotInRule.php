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

class NotInRule implements RuleInterface
{
    protected $blocked = [];
    protected $field;

    public function __construct(array $blocked = [])
    {
        $this->blocked = array_values(array_filter(array_map('trim', $blocked), 'strlen'));
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function validate($value, array $data): bool
    {
        if ($value === null || $value === '') return true;

        $val = is_scalar($value) ? (string)$value : '';
        foreach ($this->blocked as $b) {
            if ((string)$b === $val) return false;
        }
        return true;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} contains a forbidden value.";
    }
}