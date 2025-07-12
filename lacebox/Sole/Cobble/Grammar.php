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
        // 1) build each column fragment
        $cols = array_map(function(array $c) {
            return $this->getColumnSql($c);
        }, $bp->columns);

        // 2) pull out any “primary” flag
        $pk = array_filter($bp->columns, function(array $c) {
            return ! empty($c['primary']);
        });

        if (! empty($pk)) {
            $cols[] = 'PRIMARY KEY (' . $pk[0]['name'] . ')';
        }

        // 3) indexes
        foreach ($bp->indexes as $idx) {
            $cols[] = strtoupper($idx['type'])
                . ' (`' . implode('`,`', $idx['columns']) . '`)';
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
        $statements = [];
        foreach ($bp->columns as $c) {
            $statements[] = 'ADD COLUMN ' . $this->getColumnSql($c);
        }
        foreach ($bp->drops as $col) {
            $statements[] = "DROP COLUMN `{$col}`";
        }
        // etc.
        if (empty($statements)) {
            return [];
        }
        return [ sprintf(
            'ALTER TABLE `%s` %s',
            $bp->table,
            implode(', ', $statements)
        ) ];
    }

    public function compileDropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS `{$table}`";
    }

    private function getColumnSql(array $c): string
    {
        $sql = "`{$c['name']}` {$c['type']}";
        if (! empty($c['length'])) {
            $sql .= "({$c['length']})";
        }
        if (! empty($c['auto'])) {
            $sql .= ' AUTO_INCREMENT';
        }
        if (empty($c['nullable'])) {
            $sql .= ' NOT NULL';
        }
        return $sql;
    }
}
