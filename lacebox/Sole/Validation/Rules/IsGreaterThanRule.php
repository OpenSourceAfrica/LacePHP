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

class IsGreaterThanRule implements RuleInterface
{
    protected $min;

    public function __construct($min)
    {
        $this->min = $min;
    }

    public function validate($value, array $allData): bool
    {
        if (! is_numeric($value)) {
            return false;
        }
        return ((float)$value) > $this->min;
    }

    public function message(): string
    {
        return "The value must be greater than {$this->min}.";
    }
}
