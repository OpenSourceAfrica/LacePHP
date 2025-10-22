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

final class SchemaInspector
{
    public static function hasColumn($table, $column)
    {
        $pdo = ConnectionManager::getConnection();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $sql = "SELECT 1
                      FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name = :t
                       AND column_name = :c
                     LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table, ':c' => $column]);
            return (bool) $st->fetchColumn();
        }

        if ($driver === 'sqlite') {
            $st = $pdo->query("PRAGMA table_info(" . str_replace('`','', $table) . ")");
            if (!$st) return false;
            while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
                if (isset($row['name']) && strcasecmp($row['name'], $column) === 0) {
                    return true;
                }
            }
            return false;
        }

        if ($driver === 'pgsql') {
            $sql = "SELECT 1
                      FROM information_schema.columns
                     WHERE table_schema = current_schema()
                       AND table_name   = :t
                       AND column_name  = :c
                     LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table, ':c' => $column]);
            return (bool) $st->fetchColumn();
        }

        return false;
    }
}