<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
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

class MinRule implements RuleInterface
{
    protected $min;

    public function __construct(int $min)
    {
        $this->min = $min;
    }

    public function validate($value, array $all): bool
    {
        return is_string($value) && mb_strlen($value) >= $this->min;
    }

    public function message(): string
    {
        return "Minimum length is {$this->min} characters.";
    }
}