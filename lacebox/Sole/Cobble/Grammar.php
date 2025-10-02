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
        $stmts  = [];

        // If SQLite has any modify ops, let Welt use the rebuilder
        if ($driver === 'sqlite') {
            foreach ($bp->columns as $c) {
                if (isset($c['__op']) && $c['__op'] === 'modify') {
                    return []; // Welt will call SqliteRebuilder
                }
            }
        }

        // ── ADD / MODIFY columns ───────────────────────────────────────────────
        foreach ($bp->columns as $c) {
            $name   = $c['name'];
            $exists = SchemaInspector::hasColumn($bp->table, $name);
            $op     = isset($c['__op']) ? $c['__op'] : 'add';

            // If asked to modify but column doesn't exist → treat as add
            if ($op === 'modify' && !$exists) $op = 'add';

            if ($op === 'modify') {
                if ($driver === 'mysql') {
                    $stmts[] = 'MODIFY COLUMN ' . $this->getColumnSql($c, $driver);
                } elseif ($driver === 'pgsql') {
                    list($typeSql, $nullable, $hasDefault, $defaultSql) = $this->pgParts($c);
                    if ($typeSql !== null) $stmts[] = 'ALTER COLUMN "'.$name.'" TYPE '.$typeSql;
                    if ($nullable === true)  $stmts[] = 'ALTER COLUMN "'.$name.'" DROP NOT NULL';
                    if ($nullable === false) $stmts[] = 'ALTER COLUMN "'.$name.'" SET NOT NULL';
                    if ($hasDefault) $stmts[] = 'ALTER COLUMN "'.$name.'" SET DEFAULT '.$defaultSql;
                    else             $stmts[] = 'ALTER COLUMN "'.$name.'" DROP DEFAULT';
                } else {
                    // Other drivers: not implemented
                    @trigger_error("[Welt] modify not implemented for driver {$driver}", E_USER_NOTICE);
                }
            } else { // add
                if (!$exists) {
                    $stmts[] = 'ADD COLUMN ' . $this->getColumnSql($c, $driver);
                }
            }
        }

        // ── DROP PRIMARY KEY ──────────────────────────────────────────────────
        if ($bp->dropPrimary) {
            if ($driver === 'mysql') {
                $stmts[] = 'DROP PRIMARY KEY';
            } elseif ($driver === 'pgsql') {
                // assumes default PK name "{$table}_pkey"
                $stmts[] = 'DROP CONSTRAINT "'.$bp->table.'_pkey"';
            }
        }

        // ── DROP INDEXES ──────────────────────────────────────────────────────
        foreach ($bp->dropIndexes as $idxName) {
            if ($driver === 'mysql') {
                $stmts[] = 'DROP INDEX `'.$idxName.'`';
            } elseif ($driver === 'pgsql') {
                // handled as separate statement later
                $stmts[] = '__PG_DROP_INDEX__'.$idxName;
            } else {
                // SQLite handled outside ALTER (rebuilder or standalone)
                $stmts[] = '__GENERIC_DROP_INDEX__'.$idxName;
            }
        }

        // ── ADD table indexes (PRIMARY/UNIQUE/INDEX) ──────────────────────────
        foreach ($bp->indexes as $idx) {
            $type = strtoupper($idx['type']);
            $colsList = '`' . implode('`,`', $idx['columns']) . '`';
            if ($type === 'PRIMARY') {
                $stmts[] = 'ADD PRIMARY KEY (' . $colsList . ')';
            } elseif ($type === 'UNIQUE') {
                $stmts[] = 'ADD UNIQUE (' . $colsList . ')';
            } else {
                $stmts[] = 'ADD INDEX (' . $colsList . ')';
            }
        }

        // ── DROP FOREIGN KEYS ─────────────────────────────────────────────────
        foreach ($bp->dropForeigns as $fkName) {
            if ($driver === 'mysql') {
                $stmts[] = 'DROP FOREIGN KEY `'.$fkName.'`';
            } elseif ($driver === 'pgsql') {
                $stmts[] = 'DROP CONSTRAINT "'.$fkName.'"';
            } else {
                // SQLite FKs live in CREATE TABLE; handled by rebuilder
                $stmts[] = '__SQLITE_DROP_FK__'.$fkName;
            }
        }

        // ── ADD FOREIGN KEYS ──────────────────────────────────────────────────
        foreach ($bp->foreigns as $fk) {
            $name = $fk['name'] ?: $this->autoFkName($bp->table, $fk['columns']);
            if ($driver === 'mysql') {
                $cols    = '`'.implode('`,`', $fk['columns']).'`';
                $refCols = '`'.implode('`,`', (array)$fk['refCols']).'`';
                $clause  = 'ADD CONSTRAINT `'.$name.'` FOREIGN KEY ('.$cols.') REFERENCES `'.$fk['refTable'].'` ('.$refCols.')';
                if ($fk['onDelete']) $clause .= ' ON DELETE '.$fk['onDelete'];
                if ($fk['onUpdate']) $clause .= ' ON UPDATE '.$fk['onUpdate'];
                $stmts[] = $clause;
            } elseif ($driver === 'pgsql') {
                $cols    = '"'.implode('","', $fk['columns']).'"';
                $refCols = '"'.implode('","', (array)$fk['refCols']).'"';
                $clause  = 'ADD CONSTRAINT "'.$name.'" FOREIGN KEY ('.$cols.') REFERENCES "'.$fk['refTable'].'" ('.$refCols.')';
                if ($fk['onDelete']) $clause .= ' ON DELETE '.$fk['onDelete'];
                if ($fk['onUpdate']) $clause .= ' ON UPDATE '.$fk['onUpdate'];
                $stmts[] = $clause;
            } else {
                // SQLite handled in rebuilder
                $stmts[] = '__SQLITE_ADD_FK__'.json_encode($fk);
            }
        }

        // ── RENAME INDEXES ────────────────────────────────────────────────────
        foreach ($bp->renameIndexes as $ren) {
            $from = $ren['from']; $to = $ren['to'];
            if ($driver === 'mysql') {
                $stmts[] = 'RENAME INDEX `'.$from.'` TO `'.$to.'`';
            } elseif ($driver === 'pgsql') {
                // separate statement later
                $stmts[] = '__PG_RENAME_INDEX__'.$from.'__TO__'.$to;
            } else {
                // SQLite: emulate via drop/create later (rebuilder path or welt follow-up)
                $stmts[] = '__SQLITE_RENAME_INDEX__'.$from.'__TO__'.$to;
            }
        }

        // ── Compose SQL array ─────────────────────────────────────────────────
        $sqls = [];

        if (!empty($stmts)) {
            if ($driver === 'pgsql') {
                // Split out PG-only extras
                $tableAlter = [];
                $extra = [];
                foreach ($stmts as $s) {
                    if (strpos($s, '__PG_DROP_INDEX__') === 0) {
                        $idxName = substr($s, strlen('__PG_DROP_INDEX__'));
                        $extra[] = 'DROP INDEX IF EXISTS "'.$idxName.'"';
                    } elseif (strpos($s, '__PG_RENAME_INDEX__') === 0) {
                        $rest = substr($s, strlen('__PG_RENAME_INDEX__'));
                        list($from, $to) = explode('__TO__', $rest, 2);
                        $extra[] = 'ALTER INDEX "'.$from.'" RENAME TO "'.$to.'"';
                    } else {
                        $tableAlter[] = $s;
                    }
                }
                if (!empty($tableAlter)) {
                    $sqls[] = 'ALTER TABLE "'.$bp->table.'" '.implode(', ', $tableAlter);
                }
                $sqls = array_merge($sqls, $extra);
            } else {
                // MySQL & others: single ALTER TABLE with all parts
                $sqls[] = 'ALTER TABLE `'.$bp->table.'` '.implode(', ', $stmts);
            }
        }

        // ── Table rename (appended after column/index/fk changes) ─────────────
        if (!empty($bp->tableRename)) {
            if ($driver === 'mysql') {
                $sqls[] = 'RENAME TABLE `'.$bp->table.'` TO `'.$bp->tableRename.'`';
            } elseif ($driver === 'pgsql') {
                $sqls[] = 'ALTER TABLE "'.$bp->table.'" RENAME TO "'.$bp->tableRename.'"';
            } else { // sqlite
                $sqls[] = 'ALTER TABLE `'.$bp->table.'` RENAME TO `'.$bp->tableRename.'`';
            }
        }

        return $sqls;
    }


    private function autoFkName($table, array $cols): string
    {
        return $table.'_'.implode('_', $cols).'_foreign';
    }


    /**
     * Break a column definition array into Postgres-friendly pieces.
     * @return array [typeSql|null, nullable(bool|null), hasDefault(bool), defaultSql(string|NULL)]
     */
    private function pgParts(array $c): array
    {
        $type = strtolower($c['type']);
        $name = $c['name'];

        // Map MySQL-ish types to Postgres types
        $pgType = null;
        if ($type === 'varchar') {
            $len = !empty($c['length']) ? (int)$c['length'] : 255;
            $pgType = 'VARCHAR(' . $len . ')';
        } elseif ($type === 'text' || $type === 'mediumtext' || $type === 'longtext') {
            $pgType = 'TEXT';
        } elseif ($type === 'json') {
            $pgType = 'JSONB';
        } elseif ($type === 'tinyint') {
            // treat TINYINT(1) as boolean if length==1 else smallint
            $pgType = (isset($c['length']) && (int)$c['length'] === 1) ? 'BOOLEAN' : 'SMALLINT';
        } elseif ($type === 'smallint') {
            $pgType = 'SMALLINT';
        } elseif ($type === 'int' || $type === 'integer') {
            $pgType = 'INTEGER';
        } elseif ($type === 'bigint') {
            $pgType = 'BIGINT';
        } elseif ($type === 'float') {
            $pgType = 'REAL';
        } elseif ($type === 'double') {
            $pgType = 'DOUBLE PRECISION';
        } elseif ($type === 'decimal') {
            $prec = isset($c['precision']) ? (int)$c['precision'] : 8;
            $sc   = isset($c['scale']) ? (int)$c['scale'] : 2;
            $pgType = 'NUMERIC('.$prec.','.$sc.')';
        } elseif ($type === 'date') {
            $pgType = 'DATE';
        } elseif ($type === 'datetime' || $type === 'timestamp') {
            $pgType = 'TIMESTAMP';
        } elseif ($type === 'enum') {
            // Simple portable approach: TEXT with check is complex; here we keep TEXT
            $pgType = 'TEXT';
        } else {
            $pgType = strtoupper($type);
        }

        // Nullability
        $nullable = array_key_exists('nullable', $c) ? (bool)$c['nullable'] : null;

        // Default
        $hasDefault = false;
        $defaultSql = null;
        if (isset($c['defaultRaw'])) {
            $hasDefault = true;
            $defaultSql = $c['defaultRaw'];
        } elseif (array_key_exists('default', $c)) {
            $hasDefault = true;
            $defaultSql = $this->pgQuoteDefault($c['default']);
        }

        return [$pgType, $nullable, $hasDefault, $defaultSql];
    }

    private function pgQuoteDefault($value): string
    {
        if (is_bool($value))  return $value ? 'TRUE' : 'FALSE';
        if (is_int($value) || is_float($value)) return (string)$value;
        if ($value === null)  return 'NULL';
        // quote string
        return "'" . str_replace("'", "''", (string)$value) . "'";
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

    public function getSqliteAddColumnSql(array $c): string
    {
        // expose a safe "ADD COLUMN ..." payload for SQLite
        return $this->getColumnSql($c, 'sqlite');
    }
}