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

class DB {
    /** @return RawExpr */
    public static function raw($expr) { return new RawExpr($expr); }

    public static function pdo()
    {
        return ConnectionManager::getConnection();
    }

    public static function transaction(callable $cb)
    {
        return ConnectionManager::transaction($cb);
    }

    public static function begin(): void
    {
        ConnectionManager::begin();
    }

    public static function commit(): void
    {
        ConnectionManager::commit();
    }

    public static function rollBack(): void
    {
        ConnectionManager::rollBack();
    }
}