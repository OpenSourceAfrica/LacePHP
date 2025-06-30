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

namespace Lacebox\Insole\Stitching;

/**
 * SingletonTrait
 *
 * Provides a getInstance() method and prevents direct construction,
 * cloning, or unserialization.
 */
trait SingletonTrait
{
    /**
     * @var static|null
     */
    private static $instance = null;

    /**
     * Return the singleton instance of the class.
     *
     * @return static
     */
    public static function getInstance(): self
    {
        if (static::$instance === null) {
            // Allow passing constructor args if needed
            static::$instance = new static(...func_get_args());
        }
        return static::$instance;
    }

    /**
     * Prevent direct construction.
     */
    private function __construct()
    {
        // You may initialize defaults here if needed.
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}
}