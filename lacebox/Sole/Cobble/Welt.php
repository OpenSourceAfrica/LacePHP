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

        $grammar = new Grammar();
        $sqls = $grammar->compileCreate($blueprint);

        foreach ($sqls as $sql) {
            try {
                ConnectionManager::getConnection()->exec($sql);
            } catch (\PDOException $e) {
                throw new \RuntimeException(
                    "[Welt] Failed creating `{$table}`: " . $e->getMessage() . " | SQL: {$sql}",
                    (int)$e->getCode(),
                    $e
                );
            }
        }
    }

    public static function table(string $table, \Closure $cb): void
    {
        $blueprint = new Blueprint($table);
        $cb($blueprint);

        $grammar = new Grammar();
        $pdo     = ConnectionManager::getConnection();
        $driver  = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $hasModify = false;
        foreach ($blueprint->columns as $c) {
            if (isset($c['__op']) && $c['__op'] === 'modify') { $hasModify = true; break; }
        }

        if ($driver === 'sqlite' && $hasModify) {
            // Rebuild path (handles add + modify together)
            SqliteRebuilder::apply($blueprint, $grammar);
            return;
        }

        // Normal path (MySQL/Postgres, or SQLite without modify)
        $sqls = $grammar->compileAlter($blueprint);
        foreach ($sqls as $sql) {
            if (!trim($sql)) continue;
            try {
                $pdo->exec($sql);
            } catch (\PDOException $e) {
                throw new \RuntimeException(
                    "[Welt] Failed altering `{$table}`: " . $e->getMessage() . " | SQL: {$sql}",
                    (int)$e->getCode(),
                    $e
                );
            }
        }
    }

    public static function dropIfExists(string $table): void
    {
        $sql = (new Grammar)->compileDropIfExists($table);
        ConnectionManager::getConnection()->exec($sql);
    }

    public static function hasTable(string $table): bool
    {
        return SchemaInspector::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return SchemaInspector::hasColumn($table, $column);
    }

}