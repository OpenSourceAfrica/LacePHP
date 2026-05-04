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

class ArrayRule implements RuleInterface
{
    protected $field;
    public function __construct($param = null) {}

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function validate($value, array $all): bool
    {
        return is_array($value);
    }

    public function message(): string
    {
        return "Field must be an array.";
    }
}