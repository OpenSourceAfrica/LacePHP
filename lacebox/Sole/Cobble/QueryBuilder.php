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

use Lacebox\Sole\Cobble\ConnectionManager;

class QueryBuilder
{
    protected $table;
    protected $columns = ['*'];
    protected $wheres  = [];
    protected $bindings = [];

    public function __construct(string $table)
    {
        $this->table = $table;
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
        $sql = $this->toSql();
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $results = $this->get();
        return $results[0] ?? null;
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