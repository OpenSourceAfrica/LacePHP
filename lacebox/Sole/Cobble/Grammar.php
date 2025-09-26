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

class Grammar
{
    public function compileCreate(Blueprint $bp): array
    {
        $this->assertNoDuplicateColumns($bp);
        $this->assertEnumDefaultsValid($bp);

        $driver = $this->driver();
        $cols = array();
        $inlineSqlitePkAdded = false;

        // 1) build each column fragment (track inline SQLite PK AUTOINCREMENT)
        foreach ($bp->columns as $c) {
            $colSql = $this->getColumnSql($c, $driver);
            $cols[] = $colSql;

            if ($driver === 'sqlite' && $this->isInlineSqlitePk($c)) {
                $inlineSqlitePkAdded = true;
            }
        }

        // 2) primary key (first column flagged as primary)
        $pk = array_filter($bp->columns, function(array $c) {
            return !empty($c['primary']);
        });

        if (!empty($pk)) {
            // If we already inlined the PK for SQLite (INTEGER PRIMARY KEY AUTOINCREMENT),
            // do NOT add a separate PRIMARY KEY constraint again.
            if (!($driver === 'sqlite' && $inlineSqlitePkAdded)) {
                $first = array_values($pk)[0];
                $cols[] = 'PRIMARY KEY (`' . $first['name'] . '`)';
            }
        }

        // 3) indexes (table-level) – simple form kept as in your original code
        foreach ($bp->indexes as $idx) {
            $type = strtoupper($idx['type']); // primary|unique|index → PRIMARY|UNIQUE|INDEX
            $cols[] = $type . ' (`' . implode('`,`', $idx['columns']) . '`)';
        }

        // 4) assemble the SQL
        $sql = sprintf(
            'CREATE TABLE `%s` (%s)',
            $bp->table,
            implode(",\n  ", $cols)
        );

        return [$sql];
    }

    public function compileAlter(Blueprint $bp): array
    {
        $this->assertNoDuplicateColumns($bp);
        $this->assertEnumDefaultsValid($bp);

        $driver = $this->driver();
        $statements = array();

        // add columns
        foreach ($bp->columns as $c) {
            // NOTE: SQLite cannot add a PRIMARY KEY/AUTOINCREMENT after creation; only ADD COLUMN works.
            $statements[] = 'ADD COLUMN ' . $this->getColumnSql($c, $driver);
        }

        // drop columns
        foreach ($bp->drops as $col) {
            $statements[] = "DROP COLUMN `{$col}`";
        }

        // rename columns (SQLite & MySQL 8+ support)
        foreach ($bp->renames as $r) {
            $statements[] = "RENAME COLUMN `{$r['from']}` TO `{$r['to']}`";
        }

        // table indexes – simple form
        foreach ($bp->indexes as $idx) {
            $type = strtoupper($idx['type']);
            $colsList = '`' . implode('`,`', $idx['columns']) . '`';
            if ($type === 'PRIMARY') {
                $statements[] = 'ADD PRIMARY KEY (' . $colsList . ')';
            } elseif ($type === 'UNIQUE') {
                $statements[] = 'ADD UNIQUE (' . $colsList . ')';
            } else {
                $statements[] = 'ADD INDEX (' . $colsList . ')';
            }
        }

        if (empty($statements)) {
            return array();
        }

        return array(sprintf(
            'ALTER TABLE `%s` %s',
            $bp->table,
            implode(', ', $statements)
        ));
    }

    public function compileDropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS `{$table}`";
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Column compilation
    // ────────────────────────────────────────────────────────────────────────────

    private function getColumnSql(array $c, $driver): string
    {
        $type = strtolower($c['type']);
        $name = $c['name'];

        // Special case: SQLite inline PRIMARY KEY AUTOINCREMENT
        if ($driver === 'sqlite' && $this->isInlineSqlitePk($c)) {
            // Must be exactly INTEGER PRIMARY KEY AUTOINCREMENT
            return '`' . $name . '` INTEGER PRIMARY KEY AUTOINCREMENT';
        }

        // ENUM: MySQL vs SQLite
        if ($type === 'enum') {
            $allowed = isset($c['allowed']) && is_array($c['allowed']) ? $c['allowed'] : array();
            $allowedQuoted = array_map(function ($v) {
                return "'" . str_replace("'", "''", (string)$v) . "'";
            }, $allowed);

            if ($driver === 'sqlite') {
                $sql = '`' . $name . '` TEXT';
            } else {
                $sql = '`' . $name . '` ENUM(' . implode(',', $allowedQuoted) . ')';
            }
        } else {
            $sql = '`' . $name . '` ' . $type;

            // length for varchar/char/tinyint/binary types
            if (!empty($c['length']) && $this->supportsLength($type)) {
                $sql .= '(' . (int)$c['length'] . ')';
            }

            // precision/scale for decimal/double/float
            if (isset($c['precision'], $c['scale']) && $this->supportsPrecisionScale($type)) {
                $sql .= '(' . (int)$c['precision'] . ',' . (int)$c['scale'] . ')';
            }

            // UNSIGNED (MySQL only; SQLite ignores)
            if (!empty($c['unsigned']) && $this->isIntegerLike($type) && $driver !== 'sqlite') {
                $sql .= ' UNSIGNED';
            }

            // AUTO_INCREMENT (MySQL only here; SQLite handled in inline case above)
            if (!empty($c['auto']) && $this->isIntegerLike($type) && $driver !== 'sqlite') {
                $sql .= ' AUTO_INCREMENT';
            }
        }

        // NULL / NOT NULL (default NOT NULL if not set)
        $nullable = array_key_exists('nullable', $c) ? (bool)$c['nullable'] : false;
        $sql .= $nullable ? ' NULL' : ' NOT NULL';

        // DEFAULTs — skip for TEXT/BLOB/JSON; ENUM handled below
        if ($type === 'enum') {
            if (isset($c['defaultRaw'])) {
                $sql .= ' DEFAULT ' . $c['defaultRaw'];
            } elseif (array_key_exists('default', $c)) {
                $def = $c['default'];
                $allowed = isset($c['allowed']) && is_array($c['allowed']) ? $c['allowed'] : array();
                $allowedStr = array_map('strval', $allowed);

                if ($def === null && $nullable) {
                    $sql .= ' DEFAULT NULL';
                } elseif (in_array((string)$def, $allowedStr, true)) {
                    $sql .= " DEFAULT '" . str_replace("'", "''", (string)$def) . "'";
                }
            }
        } else {
            if (!$this->disallowsDefault($type)) {
                if (isset($c['defaultRaw'])) {
                    $sql .= ' DEFAULT ' . $c['defaultRaw'];
                } elseif (array_key_exists('default', $c)) {
                    $sql .= ' DEFAULT ' . $this->quoteDefault($c['default']);
                }
            }
        }

        // Inline UNIQUE per-column if set
        if (!empty($c['unique'])) {
            $sql .= ' UNIQUE';
        }

        // For SQLite ENUM emulate: append CHECK constraint inline:
        if ($type === 'enum' && $driver === 'sqlite') {
            $allowed = isset($c['allowed']) && is_array($c['allowed']) ? $c['allowed'] : array();
            if (!empty($allowed)) {
                $allowedQuoted = array_map(function ($v) {
                    return "'" . str_replace("'", "''", (string)$v) . "'";
                }, $allowed);
                $sql .= ' CHECK(`' . $name . '` IN (' . implode(',', $allowedQuoted) . '))';
            }
        }

        return $sql;
    }

    // ── validations & helpers ───────────────────────────────────────────────────

    private function isInlineSqlitePk(array $c): bool
    {
        // For SQLite, AUTOINCREMENT only valid if:
        // - integer-like type
        // - auto == true
        // - primary == true
        return !empty($c['auto'])
            && !empty($c['primary'])
            && $this->isIntegerLike(strtolower($c['type']));
    }

    private function assertNoDuplicateColumns(Blueprint $bp): void
    {
        $seen = array();
        $dups = array();

        foreach ($bp->columns as $c) {
            $n = (string)$c['name'];
            if (isset($seen[$n])) {
                $dups[$n] = true;
            } else {
                $seen[$n] = true;
            }
        }

        if (!empty($dups)) {
            throw new \InvalidArgumentException(
                "[Welt] Duplicate column(s) in `{$bp->table}`: " . implode(', ', array_keys($dups))
            );
        }
    }

    private function assertEnumDefaultsValid(Blueprint $bp): void
    {
        foreach ($bp->columns as $c) {
            if (isset($c['type']) && strtolower($c['type']) === 'enum' && array_key_exists('default', $c)) {
                $allowed = isset($c['allowed']) && is_array($c['allowed']) ? $c['allowed'] : array();
                if ($c['default'] !== null && !in_array((string)$c['default'], array_map('strval', $allowed), true)) {
                    throw new \InvalidArgumentException(
                        "[Welt] Invalid default for enum `{$c['name']}` on `{$bp->table}`. Allowed: " . implode(',', $allowed)
                    );
                }
            }
        }
    }

    private function driver(): string
    {
        try {
            $pdo = ConnectionManager::getConnection();
            $d = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            return $d ?: 'mysql';
        } catch (\Throwable $e) {
            return 'mysql';
        }
    }

    private function isIntegerLike(string $type): bool
    {
        return in_array($type, array('tinyint','smallint','int','bigint','integer'), true);
    }

    private function supportsLength(string $type): bool
    {
        return in_array($type, array('varchar','char','tinyint','varbinary','binary'), true);
    }

    private function supportsPrecisionScale(string $type): bool
    {
        return in_array($type, array('decimal','double','float'), true);
    }

    private function disallowsDefault(string $type): bool
    {
        return (strpos($type, 'text') !== false) || (strpos($type, 'blob') !== false) || $type === 'json';
    }

    private function quoteDefault($value): string
    {
        if (is_bool($value))  return $value ? '1' : '0';
        if (is_int($value))   return (string)$value;
        if (is_float($value)) return (string)$value;
        if ($value === null)  return 'NULL';
        return "'" . str_replace("'", "''", (string)$value) . "'";
    }
}