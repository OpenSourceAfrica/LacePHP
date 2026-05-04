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

class EmailRule implements RuleInterface
{
    protected $field;
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function validate($value, array $all): bool
    {
        return filter_var((string)$value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function message(): string
    {
        return 'Must be a valid email address.';
    }
}