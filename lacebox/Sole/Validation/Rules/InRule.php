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

class InRule implements RuleInterface
{
    /** @var string[] */
    protected $allowed = [];

    /** @var string|null */
    protected $field;

    public function __construct(array $allowed = [])
    {
        $this->allowed = array_values(array_filter(array_map('trim', $allowed), 'strlen'));
    }

    // optional (if your validator calls it later)
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function validate($value, array $data): bool
    {
        // Let "required" handle missing/empty
        if ($value === null || $value === '') return true;

        $val = is_scalar($value) ? (string)$value : '';
        foreach ($this->allowed as $a) {
            if ((string)$a === $val) return true;
        }
        return false;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} must be one of: " . implode(', ', $this->allowed);
    }
}