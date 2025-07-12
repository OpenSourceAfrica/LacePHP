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

namespace Lacebox\Sole\Cobble;

class Welt
{
    public static function create(string $table, \Closure $cb): void
    {
        $blueprint = new Blueprint($table);
        $cb($blueprint);
        foreach ((new Grammar)->compileCreate($blueprint) as $sql) {
            ConnectionManager::getConnection()->exec($sql);
        }
    }

    public static function table(string $table, \Closure $cb): void
    {
        $blueprint = new Blueprint($table);
        $cb($blueprint);
        foreach ((new Grammar)->compileAlter($blueprint) as $sql) {
            ConnectionManager::getConnection()->exec($sql);
        }
    }

    public static function dropIfExists(string $table): void
    {
        $sql = (new Grammar)->compileDropIfExists($table);
        ConnectionManager::getConnection()->exec($sql);
    }
}
