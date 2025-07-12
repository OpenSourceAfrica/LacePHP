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

namespace Lacebox\Sole;

/**
 * Collects debug entries throughout the request lifecycle.
 */
class DebugCollector
{
    /** @var array<int,array{time:float,msg:string}> */
    protected static $entries = [];

    /**
     * Add a new debug entry.
     *
     * @param string $msg
     * @return void
     */
    public static function add(string $msg): void
    {
        self::$entries[] = [
            'time' => microtime(true),
            'msg'  => $msg,
        ];
    }

    /**
     * Retrieve all debug entries.
     *
     * @return array<int,array{time:float,msg:string}>
     */
    public static function getEntries(): array
    {
        return self::$entries;
    }
}