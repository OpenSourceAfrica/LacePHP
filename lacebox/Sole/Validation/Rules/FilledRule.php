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

class FilledRule implements RuleInterface
{
    private $field;

    public function setField(string $field): void { $this->field = $field; }

    public function validate($value, array $all): bool
    {
        // field is present but must not be empty
        if ($value === null) return false;
        if (is_string($value)) return trim($value) !== '';
        if (is_array($value)) return count($value) > 0;
        return true;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must not be empty when present.";
    }
}