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

class RegexRule implements RuleInterface
{
    private $pattern;
    private $field;

    /**
     * @param string $pattern Example: '/^[A-Z0-9]+$/'
     */
    public function __construct(string $pattern = '')
    {
        $this->pattern = trim($pattern);
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }

    public function validate($value, array $all): bool
    {
        // regex should only run when value is present
        if ($value === null || $value === '') return true;

        if (!is_scalar($value)) return false;

        $pattern = $this->pattern;

        // If developer passed pattern without delimiters, wrap it safely.
        // e.g. regex[^[a-z]+$] => /.../
        if ($pattern !== '' && $pattern[0] !== '/' && $pattern[0] !== '#') {
            $pattern = '/' . str_replace('/', '\/', $pattern) . '/';
        }

        // invalid pattern -> fail
        if ($pattern === '' || @preg_match($pattern, '') === false) {
            return false;
        }

        return preg_match($pattern, (string)$value) === 1;
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} format is invalid.";
    }
}