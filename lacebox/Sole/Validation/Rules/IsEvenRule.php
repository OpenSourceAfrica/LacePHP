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

class IsEvenRule implements RuleInterface
{
    public function validate($value, array $allData): bool
    {
        // allow numeric strings too
        if (! is_numeric($value)) {
            return false;
        }
        return ((int)$value) % 2 === 0;
    }

    public function message(): string
    {
        return 'The value must be an even number.';
    }
}