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

namespace Lacebox\Shoelace\Guards;

/**
 * ShoeGuardInterface defines a contract for shoe-themed authentication/authorization guards.
 */
interface ShoeGuardInterface
{
    /**
     * Perform the guard check.
     *
     * @return bool True if the check passes, false otherwise.
     */
    public function check(): bool;
}