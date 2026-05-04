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
    /** @var array<string,bool> */
    private static $tableCache = [];

    /** @var array<string,bool> */
    private static $columnCache = [];

    public static function clearCache(): void
    {
        self::$tableCache = [];
        self::$columnCache = [];
    }

    public static function hasTable($table): bool
    {
        $table = (string)$table;
        $key = strtolower($table);

        if (array_key_exists($key, self::$tableCache)) {
            return self::$tableCache[$key];
        }

        $pdo = ConnectionManager::getConnection();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $ok = false;

        if ($driver === 'mysql') {
            $sql = "SELECT 1
                      FROM information_schema.tables
                     WHERE table_schema = DATABASE()
                       AND table_name = :t
                     LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table]);
            $ok = (bool)$st->fetchColumn();
        }
        elseif ($driver === 'pgsql') {
            $sql = "SELECT 1
                      FROM information_schema.tables
                     WHERE table_schema = current_schema()
                       AND table_name   = :t
                     LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table]);
            $ok = (bool)$st->fetchColumn();
        }
        elseif ($driver === 'sqlite') {
            $sql = "SELECT 1
                      FROM sqlite_master
                     WHERE type='table'
                       AND name = :t
                     LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table]);
            $ok = (bool)$st->fetchColumn();
        }

        self::$tableCache[$key] = $ok;
        return $ok;
    }

    public static function hasColumn($table, $column): bool
    {
        $table  = (string)$table;
        $column = (string)$column;

        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        // If table does not exist, column definitely doesn't
        if (!self::hasTable($table)) {
            self::$columnCache[$key] = false;
            return false;
        }

        $pdo = ConnectionManager::getConnection();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $ok = false;

        if ($driver === 'mysql') {
            $sql = "SELECT 1
                      FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name = :t
                       AND column_name = :c
                     LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table, ':c' => $column]);
            $ok = (bool)$st->fetchColumn();
        }
        elseif ($driver === 'sqlite') {
            // PRAGMA cannot be parameterized; ensure "table" is safe
            $safeTable = self::sqliteSafeIdentifier($table);
            if ($safeTable === '') {
                $ok = false;
            } else {
                $st = $pdo->query("PRAGMA table_info(" . $safeTable . ")");
                if ($st) {
                    while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
                        if (isset($row['name']) && strcasecmp($row['name'], $column) === 0) {
                            $ok = true;
                            break;
                        }
                    }
                }
            }
        }
        elseif ($driver === 'pgsql') {
            $sql = "SELECT 1
                      FROM information_schema.columns
                     WHERE table_schema = current_schema()
                       AND table_name   = :t
                       AND column_name  = :c
                     LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table, ':c' => $column]);
            $ok = (bool)$st->fetchColumn();
        }

        self::$columnCache[$key] = $ok;
        return $ok;
    }

    /**
     * Allow only safe identifiers for SQLite PRAGMA.
     * Supports: letters, numbers, underscore. (Good enough for framework tables)
     */
    private static function sqliteSafeIdentifier(string $name): string
    {
        $name = trim($name);
        $name = str_replace(['`', '"', "'"], '', $name);

        if ($name === '') return '';

        // If you want to support schema/table forms like main.users, allow dot too.
        // For now: allow letters, numbers, underscore, and dot.
        if (!preg_match('/^[A-Za-z0-9_\.]+$/', $name)) {
            return '';
        }

        return $name;
    }

    public static function columns($table): array
    {
        $pdo = ConnectionManager::getConnection();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $table = (string)$table;

        if ($driver === 'mysql') {
            $sql = "SELECT column_name
                  FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = :t
                 ORDER BY ordinal_position ASC";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table]);
            return $st->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        }

        if ($driver === 'pgsql') {
            $sql = "SELECT column_name
                  FROM information_schema.columns
                 WHERE table_schema = current_schema()
                   AND table_name = :t
                 ORDER BY ordinal_position ASC";
            $st = $pdo->prepare($sql);
            $st->execute([':t' => $table]);
            return $st->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        }

        if ($driver === 'sqlite') {
            $safeTable = preg_replace('/[^A-Za-z0-9_\.]/', '', str_replace(['`','"',"'" ], '', $table));
            if ($safeTable === '') return [];
            $st = $pdo->query("PRAGMA table_info(" . $safeTable . ")");
            if (!$st) return [];
            $cols = [];
            while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
                if (isset($row['name'])) $cols[] = $row['name'];
            }
            return $cols;
        }

        return [];
    }

}