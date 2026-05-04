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

class BooleanRule implements RuleInterface
{
    private $field;

    public function setField(string $field): void { $this->field = $field; }

    public function validate($value, array $all): bool
    {
        if ($value === null || $value === '') return true;

        if (is_bool($value)) return true;

        // accept common boolean representations
        if (is_int($value) || is_float($value)) return ($value == 0 || $value == 1);

        if (is_string($value)) {
            $v = strtolower(trim($value));
            return in_array($v, ['0','1','true','false','yes','no','on','off'], true);
        }

        return false;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must be a boolean value.";
    }
}