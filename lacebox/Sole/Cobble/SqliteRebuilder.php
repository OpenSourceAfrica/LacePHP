<?php
namespace Lacebox\Sole\Cobble;

final class SqliteRebuilder
{
    public static function apply(Blueprint $bp, Grammar $grammar): void
    {
        $pdo = ConnectionManager::getConnection();
        $table = $bp->table;

        // 0) Capture existing indexes SQL (excluding PK & autoindexes)
        $existingIndexSql = [];
        $q = $pdo->prepare("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name=:t AND sql IS NOT NULL");
        $q->execute([':t'=>$table]);
        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            $existingIndexSql[] = $row['sql']; // e.g., CREATE INDEX "idx_name" ON "table" ("col")
        }

        // 1) Read current columns
        $pragma = $pdo->query("PRAGMA table_info(" . str_replace('`','',$table) . ")");
        $cols = [];
        while ($row = $pragma->fetch(\PDO::FETCH_ASSOC)) {
            $cols[$row['name']] = $row;
        }
        if (empty($cols)) {
            throw new \RuntimeException("[Welt] SQLite rebuild: table `{$table}` not found");
        }

        // 2) Existing FKs — we’ll preserve them
        $fkRows = [];
        $fkq = $pdo->query("PRAGMA foreign_key_list(" . str_replace('`','',$table) . ")");
        while ($r = $fkq->fetch(\PDO::FETCH_ASSOC)) {
            // group by id (each FK may have multiple columns)
            $fkRows[$r['id']][] = $r;
        }
        $existingFkConstraints = [];
        foreach ($fkRows as $rows) {
            $refTable = $rows[0]['table'];
            $onUpdate = strtoupper($rows[0]['on_update']);
            $onDelete = strtoupper($rows[0]['on_delete']);
            $fromCols = [];
            $toCols   = [];
            foreach ($rows as $r) {
                $fromCols[] = $r['from'];
                $toCols[]   = $r['to'];
            }
            $cons = 'FOREIGN KEY (`'.implode('`,`',$fromCols).'`) REFERENCES `'.$refTable.'` (`'.implode('`,`',$toCols).'`)';
            if ($onDelete && $onDelete !== 'NO ACTION') $cons .= ' ON DELETE '.$onDelete;
            if ($onUpdate && $onUpdate !== 'NO ACTION') $cons .= ' ON UPDATE '.$onUpdate;
            $existingFkConstraints[] = $cons;
        }

        // 3) New column SQL (with modifies)
        $driver = 'sqlite';
        $newColsSql = [];
        $columnNamesOrdered = array_keys($cols);
        $modsByName = [];
        foreach ($bp->columns as $c) {
            if (isset($c['__op']) && $c['__op'] === 'modify') {
                $modsByName[$c['name']] = $c;
            }
        }
        foreach ($columnNamesOrdered as $name) {
            if (isset($modsByName[$name])) {
                $newColsSql[] = self::columnSqlForSqlite($modsByName[$name], $grammar);
            } else {
                $row = $cols[$name];
                $sql = '`'.$row['name'].'` '.$row['type'];
                $sql .= ((int)$row['notnull'] === 1) ? ' NOT NULL' : ' NULL';
                if ($row['dflt_value'] !== null) {
                    $sql .= ' DEFAULT '.$row['dflt_value'];
                }
                if ((int)$row['pk'] === 1) {
                    $sql .= ' PRIMARY KEY';
                }
                $newColsSql[] = $sql;
            }
        }
        // append new columns (adds)
        foreach ($bp->columns as $c) {
            if (!isset($c['__op']) || $c['__op'] !== 'modify') {
                if (!array_key_exists($c['name'], $cols)) {
                    $newColsSql[] = $grammar->getSqliteAddColumnSql($c);
                }
            }
        }

        // 4) New FK constraints from blueprint
        $newFkConstraints = [];
        foreach ($bp->foreigns as $fk) {
            $cons = 'FOREIGN KEY (`'.implode('`,`', $fk['columns']).'`) REFERENCES `'.$fk['refTable'].'` (`'.implode('`,`', (array)$fk['refCols']).'`)';
            if ($fk['onDelete']) $cons .= ' ON DELETE '.$fk['onDelete'];
            if ($fk['onUpdate']) $cons .= ' ON UPDATE '.$fk['onUpdate'];
            $newFkConstraints[] = $cons;
        }

        // 5) Create temp table including all constraints
        $temp = $table . '_rebuilt_' . substr(sha1(uniqid('', true)), 0, 6);
        $allDefs = array_merge($newColsSql, $existingFkConstraints, $newFkConstraints);
        $create = 'CREATE TABLE `'.$temp.'` ('.implode(', ', $allDefs).')';
        $pdo->exec($create);

        // 6) Copy intersecting columns
        $allNewNames = self::extractNamesFromColumnSqlList($newColsSql);
        $common = array_values(array_intersect($columnNamesOrdered, $allNewNames));
        if (!empty($common)) {
            $colsList = '`' . implode('`,`', $common) . '`';
            $pdo->exec('INSERT INTO `'.$temp.'` ('.$colsList.') SELECT '.$colsList.' FROM `'.$table.'`');
        }

        // 7) Drop old, rename new (use target rename if requested)
        $finalName = $bp->tableRename ? $bp->tableRename : $table;
        $pdo->exec('DROP TABLE `'.$table.'`');
        $pdo->exec('ALTER TABLE `'.$temp.'` RENAME TO `'.$finalName.'`');

        // 8) Re-create indexes: preserved + newly requested in blueprint
        //    Apply index renames by dropping old and creating new.
        foreach ($existingIndexSql as $sql) {
            // replace original table name inside SQL if renamed
            if ($finalName !== $table) {
                $sql = str_replace('"'.$table.'"', '"'.$finalName.'"', $sql);
                $sql = str_replace('`'.$table.'`', '`'.$finalName.'`', $sql);
            }
            $pdo->exec($sql);
        }

        // Drops by name
        foreach ($bp->dropIndexes as $name) {
            $pdo->exec('DROP INDEX IF EXISTS "'.$name.'"');
        }

        // Renames by drop/create: get column list of the old index
        foreach ($bp->renameIndexes as $ren) {
            $from = $ren['from'];
            $to = $ren['to'];
            // Look up old index definition
            $iq = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='index' AND name=:n");
            $iq->execute([':n' => $from]);
            $row = $iq->fetch(\PDO::FETCH_ASSOC);
            if ($row && !empty($row['sql'])) {
                // Parse columns from old CREATE INDEX statement
                if (preg_match('/\((.+)\)/', $row['sql'], $m)) {
                    $colsRaw = trim($m[1]); // e.g. "col1","col2"
                    $pdo->exec('DROP INDEX IF EXISTS "' . $from . '"');
                    $pdo->exec('CREATE INDEX "' . $to . '" ON "' . $finalName . '" (' . $colsRaw . ')');
                } else {
                    // couldn’t parse; fallback: drop old only
                    $pdo->exec('DROP INDEX IF EXISTS "' . $from . '"');
                }
            } else {
                // index doesn't exist → nothing to drop
            }
        }

        // Build additional index SQL from blueprint (new ones not already present)
        $idxSqls = self::sqliteIndexSqlsFromBlueprint($bp);
        foreach ($idxSqls as $sql) {
            // ensure it targets the final table name
            if ($finalName !== $bp->table) {
                $sql = str_replace('"'.$bp->table.'"', '"'.$finalName.'"', $sql);
            }
            $pdo->exec($sql);
        }
    }

    private static function columnSqlForSqlite(array $c, Grammar $grammar): string
    {
        $ref = new \ReflectionClass($grammar);
        $m   = $ref->getMethod('getColumnSql');
        $m->setAccessible(true);
        return $m->invoke($grammar, $c, 'sqlite');
    }

    private static function extractNamesFromColumnSqlList(array $list): array
    {
        $names = [];
        foreach ($list as $sql) {
            if (preg_match('/^`([^`]+)`\s/i', $sql, $m)) {
                $names[] = $m[1];
            }
        }
        return $names;
    }

    private static function sqliteIndexSqlsFromBlueprint(Blueprint $bp): array
    {
        $sqls = [];
        // Add indexes defined in $bp->indexes
        foreach ($bp->indexes as $idx) {
            $type = strtoupper($idx['type']); // PRIMARY/UNIQUE/INDEX
            if ($type === 'PRIMARY') continue; // already defined in table
            $cols = '"'.implode('","', $idx['columns']).'"';
            $name = isset($idx['name']) && $idx['name'] ? $idx['name'] : ($bp->table.'_'.implode('_', $idx['columns']).($type==='UNIQUE'?'_unique':'_index'));
            $prefix = ($type === 'UNIQUE') ? 'CREATE UNIQUE INDEX' : 'CREATE INDEX';
            $sqls[] = $prefix.' "'.$name.'" ON "'.$bp->table.'" ('.$cols.')';
        }
        // Drops requested by name: DROP INDEX IF EXISTS name
        foreach ($bp->dropIndexes as $name) {
            $sqls[] = 'DROP INDEX IF EXISTS "'.$name.'"';
        }
        return $sqls;
    }
}