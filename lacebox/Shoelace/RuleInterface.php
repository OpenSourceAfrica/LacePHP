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

namespace Lacebox\Shoelace;

interface RuleInterface
{
    /**
     * Validate the given value within the full payload.
     *
     * @param  mixed  $value
     * @param  array  $allData
     * @return bool
     */
    public function validate($value, array $allData): bool;

    /**
     * Return an error message when validation fails.
     *
     * @return string
     */
    public function message(): string;
}
