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

class ConfirmedRule implements RuleInterface
{
    private $field;

    public function setField(string $field): void { $this->field = $field; }

    public function validate($value, array $all): bool
    {
        // If absent, required should catch it
        if ($value === null) return true;

        $f = $this->field ?? '';
        $confirmKey = $f . '_confirmation';

        // for dotted paths, we only support flat confirm for now.
        // You can enhance later using dot getters.
        $other = $all[$confirmKey] ?? null;

        return (string)$value === (string)$other;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} confirmation does not match.";
    }
}