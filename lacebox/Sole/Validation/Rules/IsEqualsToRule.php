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

class IsEqualsToRule implements RuleInterface
{
    protected $target;

    public function __construct($target)
    {
        $this->target = $target;
    }

    public function validate($value, array $allData): bool
    {
        // strict comparison if both scalar, loose otherwise
        return $value == $this->target;
    }

    public function message(): string
    {
        return "The value must be equal to {$this->target}.";
    }
}