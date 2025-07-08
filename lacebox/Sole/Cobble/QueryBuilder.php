<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Sole\Cobble;

use PDO;

class QueryBuilder
{
    protected $table;
    protected $columns = ['*'];
    protected $wheres  = [];
    protected $bindings = [];
    protected $asClass = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Tell the builder “wrap each row in $className”
     */
    public function asClass(string $className): self
    {
        $this->asClass = $className;
        return $this;
    }

    public static function table(string $table): self
    {
        return new self($table);
    }

    public function select(array $cols): self
    {
        $this->columns = $cols;
        return $this;
    }

    public function where(string $column, string $op, $value): self
    {
        $this->wheres[] = "{$column} {$op} ?";
        $this->bindings[] = $value;
        return $this;
    }
    public function get(): array
    {
        $sql  = $this->toSql();
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($this->bindings);

        // If the user asked for a class, build objects
        if ($this->asClass) {
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // second arg “true” marks it as “already exists”
                $results[] = new $this->asClass($row, true);
            }
            return $results;
        }

        // otherwise return plain arrays
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): mixed
    {
        // you probably want LIMIT 1 here, but for demo:
        $stmt = ConnectionManager::getConnection()
            ->prepare($this->toSql() . ' LIMIT 1');
        $stmt->execute($this->bindings);

        if ($this->asClass) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new $this->asClass($row, true) : null;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function insert(array $data): bool
    {
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO {$this->table} ("
            . implode(',', $cols) . ") VALUES ("
            . implode(',', $placeholders) . ")";
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function update(array $data): int
    {
        $sets = [];
        foreach ($data as $col => $val) {
            $sets[] = "{$col} = ?";
            $this->bindings[] = $val;
        }
        $sql = "UPDATE {$this->table} SET "
            . implode(',', $sets)
            . ($this->wheres ? ' WHERE '.implode(' AND ',$this->wheres) : '');
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}"
            . ($this->wheres ? ' WHERE '.implode(' AND ',$this->wheres) : '');
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->rowCount();
    }

    protected function toSql(): string
    {
        return "SELECT ".implode(',', $this->columns)
            ." FROM {$this->table}"
            .($this->wheres ? ' WHERE '.implode(' AND ',$this->wheres) : '');
    }
}