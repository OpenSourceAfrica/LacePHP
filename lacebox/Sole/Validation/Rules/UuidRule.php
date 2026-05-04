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

class UuidRule implements RuleInterface
{
    private $field;

    public function setField(string $field): void { $this->field = $field; }

    public function validate($value, array $all): bool
    {
        if ($value === null || $value === '') return true;
        if (!is_string($value)) return false;

        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must be a valid UUID.";
    }
}