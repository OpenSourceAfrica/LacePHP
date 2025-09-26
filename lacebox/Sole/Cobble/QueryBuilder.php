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

use PDO;

class QueryBuilder
{
    /** @var string */
    protected $table;

    /** @var array<int, string|RawExpr> */
    protected $columns = array('*');

    /** @var array<int, array> */
    protected $wheres  = array();

    /** @var array<int, JoinClause> */
    protected $joins = array();

    /** @var array<int, array{column:string,dir:string}> */
    protected $orderBys = array();

    /** @var int|null */
    protected $limitVal  = null;
    /** @var int|null */
    protected $offsetVal = null;

    /** @var string|null */
    protected $asClass = null;

    /** @var array */
    protected $with = array();

    public function __construct($table) { $this->table = (string)$table; }

    /** @return self */
    public static function table($table) { return new self($table); }

    /** @return self */
    public function asClass($className) { $this->asClass = (string)$className; return $this; }

    /** @return self */
    public function select(array $cols) { $this->columns = $cols; return $this; }

    /** @return self */
    public function selectRaw($expr) { $this->columns[] = new RawExpr($expr); return $this; }

    /** @return self */
    public function with(array $relations) { $this->with = $relations; return $this; }

    // ── Simple JOINs + closure JOINs ─────────────────────────────────────────

    /**
     * join('customers', 'customers.msisdn', '=', 'orders.msisdn')
     * join(Database::COUNTRY, Database::COUNTRY.'.id', '=', Database::CUSTOMERS.'.country_id')
     * leftJoin('w', function(JoinClause $j){ $j->on('w.a','=','b.a')->on('w.flag','=',1); })
     */
    public function join($table, $first = null, $operator = null, $second = null, $type = 'INNER') {
        if (is_callable($first)) {
            $clause = new JoinClause($type, $table);
            $first($clause);
            $this->joins[] = $clause;
            return $this;
        }

        $clause = new JoinClause($type, $table);
        if ($first !== null && $operator !== null) {
            $clause->on($first, $operator, $second);
        }
        $this->joins[] = $clause;
        return $this;
    }

    public function leftJoin($table, $first = null, $operator = null, $second = null) {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin($table, $first = null, $operator = null, $second = null) {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    // ── WHEREs (arrays, groups, OR, raw, IN, BETWEEN) ───────────────────────

    public function where($column, $operator = null, $value = null, $boolean = 'AND') {
        if (is_callable($column)) {
            $qb = new self($this->table);
            $column($qb);
            $this->wheres[] = array(
                'type'    => 'group',
                'boolean' => strtoupper((string)$boolean),
                'wheres'  => $qb->wheres
            );
            return $this;
        }

        if (is_array($column) && $operator === null && $value === null) {
            $qb = new self($this->table);
            $isAssoc = array_keys($column) !== range(0, count($column) - 1);
            if ($isAssoc) {
                foreach ($column as $col => $val) {
                    $qb->where($col, '=', $val);
                }
            } else {
                foreach ($column as $triple) {
                    $c  = isset($triple[0]) ? $triple[0] : null;
                    $op = isset($triple[1]) ? $triple[1] : '=';
                    $val= isset($triple[2]) ? $triple[2] : null;
                    $qb->where($c, $op, $val);
                }
            }
            $this->wheres[] = array(
                'type'    => 'group',
                'boolean' => strtoupper((string)$boolean),
                'wheres'  => $qb->wheres
            );
            return $this;
        }

        $fragment = $this->compileBasicWhere($column, $operator, $value);
        $this->wheres[] = array_merge(array(
            'type'    => 'basic',
            'boolean' => strtoupper((string)$boolean)
        ), $fragment);
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null) {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereRaw($sql, $bindings = array(), $boolean = 'AND') {
        $this->wheres[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $sql . ')',
            'bindings' => (array)$bindings
        );
        return $this;
    }

    protected function compileBasicWhere($column, $op, $value) {
        if ($value instanceof RawExpr) {
            return array('sql' => $column . ' ' . $op . ' ' . $value->get(), 'bindings' => array());
        }
        return array('sql' => $column . ' ' . $op . ' ?', 'bindings' => array($value));
    }

    // IN / NOT IN
    public function whereIn($column, array $values, $boolean = 'AND', $not = false) {
        if (empty($values)) {
            $this->wheres[] = array(
                'type'     => 'raw',
                'boolean'  => strtoupper((string)$boolean),
                'sql'      => $not ? '(1=1 AND 0=1)' : '(0=1)',
                'bindings' => array()
            );
            return $this;
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $notSql = $not ? 'NOT ' : '';
        $this->wheres[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $column . ' ' . $notSql . 'IN (' . $placeholders . '))',
            'bindings' => array_values($values)
        );
        return $this;
    }
    public function orWhereIn($column, array $values) { return $this->whereIn($column, $values, 'OR', false); }
    public function whereNotIn($column, array $values, $boolean = 'AND') { return $this->whereIn($column, $values, $boolean, true); }
    public function orWhereNotIn($column, array $values) { return $this->whereIn($column, $values, 'OR', true); }

    // BETWEEN / NOT BETWEEN
    public function whereBetween($column, $from, $to, $boolean = 'AND', $not = false) {
        $notSql = $not ? 'NOT ' : '';
        $this->wheres[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $column . ' ' . $notSql . 'BETWEEN ? AND ?)',
            'bindings' => array($from, $to)
        );
        return $this;
    }
    public function orWhereBetween($column, $from, $to) { return $this->whereBetween($column, $from, $to, 'OR', false); }
    public function whereNotBetween($column, $from, $to, $boolean = 'AND') { return $this->whereBetween($column, $from, $to, $boolean, true); }
    public function orWhereNotBetween($column, $from, $to) { return $this->whereBetween($column, $from, $to, 'OR', true); }

    // ── Ordering + limiting + pagination ─────────────────────────────────────

    public function orderBy($column, $direction = 'ASC') {
        $dir = strtoupper((string)$direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBys[] = array('column' => (string)$column, 'dir' => $dir);
        return $this;
    }

    public function limit($n)  { $this->limitVal  = max(0, (int)$n); return $this; }
    public function offset($n) { $this->offsetVal = max(0, (int)$n); return $this; }

    public function forPage($page, $perPage) {
        $page = max(1, (int)$page);
        $per  = max(1, (int)$perPage);
        return $this->limit($per)->offset(($page - 1) * $per);
    }

    /** Count rows respecting joins and where (ignores order/limit/offset) */
    public function count() {
        list($sql, $bindings) = $this->compileSelectForCount();
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) (isset($row['aggregate']) ? $row['aggregate'] : 0);
    }

    /**
     * Simple paginator struct:
     * ['data'=>array, 'total'=>int, 'per_page'=>int, 'current_page'=>int, 'last_page'=>int]
     */
    public function paginate($perPage = 15, $page = 1) {
        $total = $this->count();
        $last  = (int) max(1, (int)ceil($total / max(1, (int)$perPage)));

        $this->forPage((int)$page, (int)$perPage);
        $data = $this->get();

        return array(
            'data'         => $data,
            'total'        => $total,
            'per_page'     => (int)$perPage,
            'current_page' => (int)$page,
            'last_page'    => $last
        );
    }

    // ── Reads ────────────────────────────────────────────────────────────────

    public function get() {
        list($sql, $bindings) = $this->compileSelect();
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($bindings);

        if ($this->asClass) {
            $results = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $obj = new $this->asClass($row, true);
                if (!empty($this->with) && method_exists($obj, 'with')) {
                    $obj->with($this->with);
                    foreach ($this->with as $rel) {
                        if (method_exists($obj, $rel)) { $obj->$rel; }
                    }
                }
                $results[] = $obj;
            }
            return $results;
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Nullable param type is OK in PHP 7.1+ */
    public function first(?string $column = null) {
        list($sql, $bindings) = $this->compileSelect(' LIMIT 1');
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($bindings);

        if ($this->asClass) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
            $obj = new $this->asClass($row, true);
            if (!empty($this->with) && method_exists($obj, 'with')) {
                $obj->with($this->with);
                foreach ($this->with as $rel) {
                    if (method_exists($obj, $rel)) { $obj->$rel; }
                }
            }
            return $column ? (isset($obj->{$column}) ? $obj->{$column} : null) : $obj;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return $column ? (isset($row[$column]) ? $row[$column] : null) : $row;
    }

    public function value($column) {
        $this->select(array($column));
        return $this->first($column);
    }

    // ── Writes ───────────────────────────────────────────────────────────────

    public function insertGetId(array $data) {
        $this->insert($data);
        return (int) ConnectionManager::getConnection()->lastInsertId();
    }

    public function insert(array $data) {
        $cols = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . $placeholders . ")";
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function update(array $data) {
        $sets = array();
        foreach ($data as $col => $val) { $sets[] = "`{$col}` = ?"; }
        $setClause = implode(', ', $sets);

        list($whereSql, $whereBindings) = $this->buildWhereSql();

        $sql = "UPDATE `{$this->table}` SET {$setClause}" . ($whereSql ? " WHERE {$whereSql}" : '');
        $bindings = array_merge(array_values($data), $whereBindings);

        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($bindings);
        return (int)$stmt->rowCount();
    }

    public function delete() {
        list($whereSql, $whereBindings) = $this->buildWhereSql();
        $sql = "DELETE FROM {$this->table}" . ($whereSql ? " WHERE {$whereSql}" : '');
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($whereBindings);
        return (int)$stmt->rowCount();
    }

    // ── SQL compilation helpers ──────────────────────────────────────────────

    protected function compileSelect($tail = '') {
        $cols = array();
        foreach ($this->columns as $c) {
            $cols[] = ($c instanceof RawExpr) ? $c->get() : $c;
        }

        $sql = "SELECT " . implode(',', $cols) . " FROM {$this->table}";
        $bindings = array();

        foreach ($this->joins as $join) {
            list($jSql, $jBind) = $join->toSqlAndBindings();
            $sql .= " " . $jSql;
            $bindings = array_merge($bindings, $jBind);
        }

        list($whereSql, $whereBindings) = $this->buildWhereSql();
        if ($whereSql) {
            $sql .= " WHERE " . $whereSql;
            $bindings = array_merge($bindings, $whereBindings);
        }

        if (!empty($this->orderBys)) {
            $parts = array();
            foreach ($this->orderBys as $o) {
                $parts[] = $o['column'] . ' ' . $o['dir'];
            }
            $sql .= " ORDER BY " . implode(', ', $parts);
        }

        if ($this->limitVal !== null)  { $sql .= " LIMIT " . (int)$this->limitVal; }
        if ($this->offsetVal !== null) { $sql .= " OFFSET " . (int)$this->offsetVal; }

        $sql .= $tail;

        return array($sql, $bindings);
    }

    protected function compileSelectForCount() {
        $origCols   = $this->columns;
        $origOrder  = $this->orderBys;
        $origLimit  = $this->limitVal;
        $origOffset = $this->offsetVal;

        $this->columns  = array(new RawExpr('COUNT(*) AS aggregate'));
        $this->orderBys = array();
        $this->limitVal = null;
        $this->offsetVal= null;

        $result = $this->compileSelect();

        // restore
        $this->columns  = $origCols;
        $this->orderBys = $origOrder;
        $this->limitVal = $origLimit;
        $this->offsetVal= $origOffset;

        return $result;
    }

    protected function buildWhereSql() {
        if (empty($this->wheres)) return array('', array());

        $bindings = array();
        $sql = $this->renderWhereGroup($this->wheres, $bindings, true);

        return array($sql, $bindings);
    }

    protected function renderWhereGroup(array $nodes, array &$bindings, $isRoot = false) {
        $parts = array();

        foreach ($nodes as $i => $node) {
            $bool = isset($node['boolean']) ? $node['boolean'] : 'AND';

            if ($node['type'] === 'basic') {
                $bind = isset($node['bindings']) ? $node['bindings'] : array();
                $bindings = array_merge($bindings, $bind);
                $parts[] = ($i === 0 ? '' : ' ' . $bool . ' ') . $node['sql'];

            } elseif ($node['type'] === 'raw') {
                $bindings = array_merge($bindings, isset($node['bindings']) ? $node['bindings'] : array());
                $parts[] = ($i === 0 ? '' : ' ' . $bool . ' ') . $node['sql'];

            } elseif ($node['type'] === 'group') {
                $inner = $this->renderWhereGroup(isset($node['wheres']) ? $node['wheres'] : array(), $bindings);
                $parts[] = ($i === 0 ? '' : ' ' . $bool . ' ') . '(' . $inner . ')';
            }
        }

        $sql = implode('', $parts);
        return $isRoot ? $sql : ($sql !== '' ? $sql : '1=1');
    }
}