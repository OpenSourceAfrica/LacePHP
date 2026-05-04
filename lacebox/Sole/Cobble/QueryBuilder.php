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

    /** @var bool */
    protected $asArray = false;

    /** @var array */
    protected $with = array();

    /** @var array<int, string|RawExpr> */
    protected $groupBys = array();

    /** @var array<int, array> */
    protected $havings = array();

    /** @var string|null */
    protected $randomOrder = null;

    /** @var string|null */
    protected $pdoDriver = null;


    public function __construct($table) { $this->table = (string)$table; }

    /** @return self */
    public static function table($table) { return new self($table); }

    /** @return self */
    public function asClass($className)
    {
        $this->asClass = (string)$className;
        // If dev explicitly chose class hydration, default to object mode
        // (unless they later call asArray())
        $this->asArray = false;
        return $this;
    }

    /** @return self */
    public function asArray(bool $on = true) { $this->asArray = $on; return $this; }

    /** Alias: $qb->array() */
    public function array(bool $on = true) { return $this->asArray($on); }

    /** @return self */
    public function select(array $cols) { $this->columns = $cols; return $this; }

    /** @return self */
    public function selectRaw($expr)
    {
        // If no explicit select() was called, replace default "*"
        if ($this->columns === array('*')) {
            $this->columns = array(new RawExpr($expr));
            return $this;
        }

        $this->columns[] = new RawExpr($expr);
        return $this;
    }

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

    // NULL / NOT NULL ---------------------------------------------------------

    /**
     * whereNull('group_id')
     * whereNull(['deleted_at','archived_at'])  // AND group of null checks
     */
    public function whereNull($column, $boolean = 'AND', $not = false)
    {
        // If an array is provided, group them with AND
        if (is_array($column)) {
            $qb = new self($this->table);
            foreach ($column as $col) {
                $qb->whereNull($col);
            }
            $this->wheres[] = array(
                'type'    => 'group',
                'boolean' => strtoupper((string)$boolean),
                'wheres'  => $qb->wheres
            );
            return $this;
        }

        $this->wheres[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $column . ($not ? ' IS NOT NULL' : ' IS NULL') . ')',
            'bindings' => array()
        );
        return $this;
    }

    /** orWhereNull('group_id') */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'OR', false);
    }

    /** whereNotNull('group_id') */
    public function whereNotNull($column, $boolean = 'AND')
    {
        return $this->whereNull($column, $boolean, true);
    }

    /** orWhereNotNull('group_id') */
    public function orWhereNotNull($column)
    {
        return $this->whereNull($column, 'OR', true);
    }


    // ── Ordering + limiting + pagination ─────────────────────────────────────

//    public function orderBy($column, $direction = 'ASC') {
//        $dir = strtoupper((string)$direction) === 'DESC' ? 'DESC' : 'ASC';
//        $this->orderBys[] = array('column' => (string)$column, 'dir' => $dir);
//        return $this;
//    }

    public function orderBy($column, $direction = 'ASC')
    {
        // Allow shorthand: orderBy('RAND')
        if ($direction === 'ASC' && is_string($column)) {
            $maybe = strtoupper(trim($column));
            if ($maybe === 'RAND' || $maybe === 'RANDOM') {
                // store as raw order part
                $this->orderBys[] = array('column' => $this->randomFunction(), 'dir' => '');
                return $this;
            }
        }

        $dir = strtoupper((string)$direction);

        // Special: allow orderBy('anything','RAND') or orderBy('anything','RANDOM')
        if ($dir === 'RAND' || $dir === 'RANDOM') {
            $this->orderBys[] = array('column' => $this->randomFunction(), 'dir' => '');
            return $this;
        }

        // Normal ASC/DESC
        $dir = ($dir === 'DESC') ? 'DESC' : 'ASC';
        $this->orderBys[] = array('column' => (string)$column, 'dir' => $dir);
        return $this;
    }

    public function orderByDesc($column) {
        return $this->orderBy($column, 'DESC');
    }

    public function orderByAsc($column) {
        return $this->orderBy($column, 'ASC');
    }

    public function limit($n)  { $this->limitVal  = max(0, (int)$n); return $this; }
    public function offset($n) { $this->offsetVal = max(0, (int)$n); return $this; }

    public function forPage($page, $perPage) {
        $page = max(1, (int)$page);
        $per  = max(1, (int)$perPage);
        return $this->limit($per)->offset(($page - 1) * $per);
    }

    /**
     * groupBy('country_id')
     * groupBy(['country_id','status'])
     * groupBy('country_id', 'status')
     * groupBy(new RawExpr('DATE(created_at)'))
     */
    public function groupBy($columns)
    {
        $cols = func_num_args() > 1 ? func_get_args() : $columns;

        if (!is_array($cols)) {
            $cols = array($cols);
        }

        foreach ($cols as $c) {
            if ($c instanceof RawExpr) {
                $this->groupBys[] = $c;
            } else {
                $this->groupBys[] = (string)$c;
            }
        }

        return $this;
    }

    public function having($column, $operator = null, $value = null, $boolean = 'AND')
    {
        // grouped HAVING: ->having(function($q){ ... })
        if (is_callable($column)) {
            $qb = new self($this->table);
            $column($qb);

            $this->havings[] = array(
                'type'    => 'group',
                'boolean' => strtoupper((string)$boolean),
                'havings' => $qb->havings
            );
            return $this;
        }

        // array HAVING (assoc or triples) like where()
        if (is_array($column) && $operator === null && $value === null) {
            $qb = new self($this->table);
            $isAssoc = array_keys($column) !== range(0, count($column) - 1);

            if ($isAssoc) {
                foreach ($column as $col => $val) {
                    $qb->having($col, '=', $val);
                }
            } else {
                foreach ($column as $triple) {
                    $c   = isset($triple[0]) ? $triple[0] : null;
                    $op  = isset($triple[1]) ? $triple[1] : '=';
                    $val = isset($triple[2]) ? $triple[2] : null;
                    $qb->having($c, $op, $val);
                }
            }

            $this->havings[] = array(
                'type'    => 'group',
                'boolean' => strtoupper((string)$boolean),
                'havings' => $qb->havings
            );
            return $this;
        }

        $fragment = $this->compileBasicHaving($column, $operator, $value);

        $this->havings[] = array_merge(array(
            'type'    => 'basic',
            'boolean' => strtoupper((string)$boolean)
        ), $fragment);

        return $this;
    }

    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    public function havingRaw($sql, $bindings = array(), $boolean = 'AND')
    {
        $this->havings[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $sql . ')',
            'bindings' => (array)$bindings
        );
        return $this;
    }

    public function orHavingRaw($sql, $bindings = array())
    {
        return $this->havingRaw($sql, $bindings, 'OR');
    }

    public function havingNull($column, $boolean = 'AND', $not = false)
    {
        // If an array is provided, group them with AND
        if (is_array($column)) {
            $qb = new self($this->table);
            foreach ($column as $col) {
                $qb->havingNull($col);
            }
            $this->havings[] = array(
                'type'    => 'group',
                'boolean' => strtoupper((string)$boolean),
                'havings' => $qb->havings
            );
            return $this;
        }

        $this->havings[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $column . ($not ? ' IS NOT NULL' : ' IS NULL') . ')',
            'bindings' => array()
        );

        return $this;
    }

    public function orHavingNull($column)
    {
        return $this->havingNull($column, 'OR', false);
    }

    public function havingNotNull($column, $boolean = 'AND')
    {
        return $this->havingNull($column, $boolean, true);
    }

    public function orHavingNotNull($column)
    {
        return $this->havingNull($column, 'OR', true);
    }

    // IN / NOT IN (HAVING)
    public function havingIn($column, array $values, $boolean = 'AND', $not = false)
    {
        if (empty($values)) {
            $this->havings[] = array(
                'type'     => 'raw',
                'boolean'  => strtoupper((string)$boolean),
                'sql'      => $not ? '(1=1 AND 0=1)' : '(0=1)',
                'bindings' => array()
            );
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $notSql = $not ? 'NOT ' : '';

        $this->havings[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $column . ' ' . $notSql . 'IN (' . $placeholders . '))',
            'bindings' => array_values($values)
        );

        return $this;
    }

    public function orHavingIn($column, array $values)
    {
        return $this->havingIn($column, $values, 'OR', false);
    }

    public function havingNotIn($column, array $values, $boolean = 'AND')
    {
        return $this->havingIn($column, $values, $boolean, true);
    }

    public function orHavingNotIn($column, array $values)
    {
        return $this->havingIn($column, $values, 'OR', true);
    }

    // BETWEEN / NOT BETWEEN (HAVING)
    public function havingBetween($column, $from, $to, $boolean = 'AND', $not = false)
    {
        $notSql = $not ? 'NOT ' : '';
        $this->havings[] = array(
            'type'     => 'raw',
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $column . ' ' . $notSql . 'BETWEEN ? AND ?)',
            'bindings' => array($from, $to)
        );
        return $this;
    }

    public function orHavingBetween($column, $from, $to)
    {
        return $this->havingBetween($column, $from, $to, 'OR', false);
    }

    public function havingNotBetween($column, $from, $to, $boolean = 'AND')
    {
        return $this->havingBetween($column, $from, $to, $boolean, true);
    }

    public function orHavingNotBetween($column, $from, $to)
    {
        return $this->havingBetween($column, $from, $to, 'OR', true);
    }

    protected function compileBasicHaving($column, $op, $value)
    {
        if ($value instanceof RawExpr) {
            return array('sql' => $column . ' ' . $op . ' ' . $value->get(), 'bindings' => array());
        }
        return array('sql' => $column . ' ' . $op . ' ?', 'bindings' => array($value));
    }

    public function groupByRaw(string $expr) { return $this->groupBy(new RawExpr($expr)); }

    /** Count rows respecting joins and where (ignores order/limit/offset) */
    public function count()
    {
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

        if ($this->asClass && !$this->asArray) {
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

        if ($this->asClass && !$this->asArray) {
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

    /**
     * Insert supports:
     *  - Single row: insert(['a'=>1]) OR insert((object)['a'=>1])
     *  - Bulk rows:  insert([ ['a'=>1], (object)['a'=>2], ... ])
     *
     * Returns true for single insert, or inserted row count for bulk insert.
     *
     * @param array|object $data
     * @return bool|int
     */
    public function insert($data)
    {
        // Bulk insert if it's a list/iterable of rows
        if ($this->isBulkRows($data)) {
            $rows = $this->normaliseRows($data);
            return $this->insertMany($rows);
        }

        // Single row
        $row = $this->normaliseRow($data);

        if (empty($row)) {
            // nothing to insert
            return false;
        }

        $cols = array_keys($row);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . $placeholders . ")";
        $stmt = ConnectionManager::getConnection()->prepare($sql);

        return $stmt->execute(array_values($row));
    }

    /**
     * Bulk insert helper (multi-row VALUES).
     * Uses batches to avoid exceeding placeholder limits (important for SQLite).
     *
     * @param array<int,array<string,mixed>> $rows
     * @param int $batchSize
     * @return int inserted rows
     */
    protected function insertMany(array $rows, $batchSize = 500)
    {
        if (empty($rows)) return 0;

        // Determine columns from the first non-empty row
        $columns = null;
        foreach ($rows as $r) {
            if (!empty($r)) { $columns = array_keys($r); break; }
        }
        if (!$columns) return 0;

        // Normalise: ensure each row has all columns, keep consistent column order
        $normalised = array();
        foreach ($rows as $r) {
            $row = array();
            foreach ($columns as $c) {
                $row[$c] = array_key_exists($c, $r) ? $r[$c] : null;
            }
            $normalised[] = $row;
        }

        $totalInserted = 0;
        $conn = ConnectionManager::getConnection();

        // Batch insert to avoid too many placeholders
        $chunks = array_chunk($normalised, (int)$batchSize);

        foreach ($chunks as $chunkRows) {
            $placeholdersPerRow = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $valuesSql = implode(',', array_fill(0, count($chunkRows), $placeholdersPerRow));

            $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES " . $valuesSql;

            $bindings = array();
            foreach ($chunkRows as $row) {
                foreach ($columns as $c) {
                    $bindings[] = $row[$c];
                }
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($bindings);
            $totalInserted += (int)$stmt->rowCount();
        }

        return $totalInserted;
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

        if (!empty($this->groupBys)) {
            $parts = array();
            foreach ($this->groupBys as $g) {
                $parts[] = ($g instanceof RawExpr) ? $g->get() : $g;
            }
            $sql .= " GROUP BY " . implode(', ', $parts);
        }

        list($havingSql, $havingBindings) = $this->buildHavingSql();
        if ($havingSql) {
            $sql .= " HAVING " . $havingSql;
            $bindings = array_merge($bindings, $havingBindings);
        }

        if (!empty($this->orderBys)) {
            $parts = array();
            foreach ($this->orderBys as $o) {
                $part = $o['column'];
                if (isset($o['dir']) && $o['dir'] !== '') {
                    $part .= ' ' . $o['dir'];
                }
                $parts[] = $part;
            }
            $sql .= " ORDER BY " . implode(', ', $parts);
        }

        if ($this->limitVal !== null)  { $sql .= " LIMIT " . (int)$this->limitVal; }
        if ($this->offsetVal !== null) { $sql .= " OFFSET " . (int)$this->offsetVal; }

        $sql .= $tail;

        return array($sql, $bindings);
    }

    protected function compileSelectForCount()
    {
        $origCols   = $this->columns;
        $origOrder  = $this->orderBys;
        $origLimit  = $this->limitVal;
        $origOffset = $this->offsetVal;

        // If query has GROUP BY or HAVING, count groups safely
        if (!empty($this->groupBys) || !empty($this->havings)) {

            // Inner query must NOT select columns (avoid duplicate column names like `id`)
            $this->columns  = array(new RawExpr('1'));
            $this->orderBys = array();
            $this->limitVal = null;
            $this->offsetVal= null;

            // Build inner grouped SQL (includes joins, where, group by, having)
            list($innerSql, $bindings) = $this->compileSelect();

            // Wrap it
            $sql = "SELECT COUNT(*) AS aggregate FROM ({$innerSql}) AS lace_count";

            // restore
            $this->columns  = $origCols;
            $this->orderBys = $origOrder;
            $this->limitVal = $origLimit;
            $this->offsetVal= $origOffset;

            return array($sql, $bindings);
        }

        // Normal (no group/having): regular COUNT(*)
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

    protected function compileCountableSubquery()
    {
        // Save original state
        $origCols   = $this->columns;
        $origOrder  = $this->orderBys;
        $origLimit  = $this->limitVal;
        $origOffset = $this->offsetVal;

        // Make it count-friendly:
        // - remove ORDER/LIMIT/OFFSET
        // - select a constant so we only return 1 row per group (fast)
        $this->columns  = array(new RawExpr('1'));
        $this->orderBys = array();
        $this->limitVal = null;
        $this->offsetVal= null;

        list($sql, $bindings) = $this->compileSelect();

        // Restore
        $this->columns  = $origCols;
        $this->orderBys = $origOrder;
        $this->limitVal = $origLimit;
        $this->offsetVal= $origOffset;

        return array($sql, $bindings);
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

    protected function buildHavingSql()
    {
        if (empty($this->havings)) return array('', array());

        $bindings = array();
        $sql = $this->renderHavingGroup($this->havings, $bindings, true);

        return array($sql, $bindings);
    }

    protected function renderHavingGroup(array $nodes, array &$bindings, $isRoot = false)
    {
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
                $inner = $this->renderHavingGroup(isset($node['havings']) ? $node['havings'] : array(), $bindings);
                $parts[] = ($i === 0 ? '' : ' ' . $bool . ' ') . '(' . $inner . ')';
            }
        }

        $sql = implode('', $parts);
        return $isRoot ? $sql : ($sql !== '' ? $sql : '1=1');
    }

    public function exists(): bool
    {
        // Temporarily override select columns to "1" and limit 1
        $origCols   = $this->columns;
        $origOrder  = $this->orderBys;
        $origLimit  = $this->limitVal;
        $origOffset = $this->offsetVal;

        $this->columns  = array(new RawExpr('1'));
        $this->orderBys = array();
        $this->limitVal = 1;
        $this->offsetVal= null;

        list($sql, $bindings) = $this->compileSelect();
        $stmt = ConnectionManager::getConnection()->prepare($sql);
        $stmt->execute($bindings);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // restore
        $this->columns  = $origCols;
        $this->orderBys = $origOrder;
        $this->limitVal = $origLimit;
        $this->offsetVal= $origOffset;

        return $row ? true : false;
    }

    /** Convenience */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Returns correct random function for the connected database.
     */
    protected function randomFunction()
    {
        $driver = ConnectionManager::getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        // defaults that work for most
        if ($driver === 'sqlite') return 'RANDOM()';
        if ($driver === 'pgsql')  return 'RANDOM()';
        if ($driver === 'mysql')  return 'RAND()';
        if ($driver === 'sqlsrv') return 'NEWID()';

        // fallback: try SQL-standard-ish
        return 'RANDOM()';
    }

    /**
     * Random order across DBs:
     * SQLite/PG: RANDOM()
     * MySQL:     RAND()
     * SQLServer: NEWID()
     */
    public function inRandomOrder()
    {
        $this->randomOrder = $this->randomFunction();
        return $this;
    }

    /** Alias */
    public function orderByRandom()
    {
        return $this->inRandomOrder();
    }

    /**
     * Determine if $data represents a bulk insert:
     * - array of arrays/objects (numeric keys)
     * - Traversable of arrays/objects
     *
     * @param mixed $data
     * @return bool
     */
    protected function isBulkRows($data)
    {
        if ($data instanceof \Traversable) {
            // assume bulk (iterator of rows)
            return true;
        }

        if (!is_array($data)) {
            return false;
        }

        if (empty($data)) {
            return false;
        }

        // Numeric array with first element being array/object = bulk
        return isset($data[0]) && (is_array($data[0]) || is_object($data[0]));
    }

    /**
     * Normalise a single row from array|object into array<string,mixed>
     *
     * @param array|object $row
     * @return array
     */
    protected function normaliseRow($row)
    {
        if (is_array($row)) {
            return $row;
        }

        if (is_object($row)) {
            // Works for stdClass and simple DTOs; for models you can implement toArray()
            if (method_exists($row, 'toArray')) {
                $arr = $row->toArray();
                return is_array($arr) ? $arr : (array)$row;
            }
            return (array)$row;
        }

        return array();
    }

    /**
     * Normalise bulk rows (array|Traversable) into array<int,array<string,mixed>>
     *
     * @param array|\Traversable $rows
     * @return array
     */
    protected function normaliseRows($rows)
    {
        $out = array();

        if ($rows instanceof \Traversable) {
            foreach ($rows as $r) {
                $out[] = $this->normaliseRow($r);
            }
            return $out;
        }

        foreach ((array)$rows as $r) {
            $out[] = $this->normaliseRow($r);
        }
        return $out;
    }

    /**
     * Fetch results in chunks.
     *
     * Important:
     * - For stable chunking, you should set an orderBy() (recommended).
     * - This uses LIMIT/OFFSET (works everywhere including SQLite).
     *
     * Callback:
     * - Receives the chunk results (arrays or objects depending on hydration mode)
     * - If callback returns === false, chunking stops early.
     *
     * @param int $count
     * @param callable $callback function(array $results): (void|bool)
     * @return bool
     */
    public function chunk($count, $callback)
    {
        $count = max(1, (int)$count);

        // Work on a clone so we don't permanently mutate the original builder
        $base = clone $this;

        $page = 1;

        while (true) {
            $qb = clone $base;
            $qb->limit($count)->offset(($page - 1) * $count);

            $results = $qb->get();
            if (empty($results)) {
                break;
            }

            $res = call_user_func($callback, $results);
            if ($res === false) {
                return false;
            }

            // If less than chunk size, no more data
            if (count($results) < $count) {
                break;
            }

            $page++;
        }

        return true;
    }

    /**
     * Chunk results by a numeric increasing key (keyset pagination).
     * Auto-adds the id column to SELECT when missing.
     *
     * @param int $count
     * @param callable $callback
     * @param string $column
     * @return bool
     */
    public function chunkById($count, $callback, $column = 'id')
    {
        $count = max(1, (int)$count);

        // Base clone (so we don't mutate the original builder)
        $base = clone $this;

        // Ensure deterministic ordering
        $base->orderByAsc($column);

        // Ensure id column is in SELECT (important when using selectRaw)
        $base->ensureChunkIdSelected($column);

        $lastId = null;

        while (true) {
            $qb = clone $base;

            if ($lastId !== null) {
                $qb->where($column, '>', $lastId);
            }

            $qb->limit($count);

            $results = $qb->get();
            if (empty($results)) {
                break;
            }

            $res = call_user_func($callback, $results);
            if ($res === false) {
                return false;
            }

            // Determine lastId from the last row
            $lastRow = $results[count($results) - 1];
            $idKey = $this->stripAlias($column);

            if (is_object($lastRow)) {
                // prefer alias key
                if (isset($lastRow->{$idKey})) {
                    $lastId = $lastRow->{$idKey};
                } elseif (isset($lastRow->id)) {
                    $lastId = $lastRow->id;
                } else {
                    $lastId = null;
                }
            } else {
                if (isset($lastRow[$idKey])) {
                    $lastId = $lastRow[$idKey];
                } elseif (isset($lastRow['id'])) {
                    $lastId = $lastRow['id'];
                } else {
                    $lastId = null;
                }
            }

            if ($lastId === null) {
                // Can't progress safely; stop to avoid infinite loop
                break;
            }

            if (count($results) < $count) {
                break;
            }
        }

        return true;
    }

    /**
     * Helper: turn "u.id" into "id" for array/object access.
     * @param string $col
     * @return string
     */
    protected function stripAlias($col)
    {
        $col = (string)$col;
        $pos = strrpos($col, '.');
        return ($pos === false) ? $col : substr($col, $pos + 1);
    }

    /**
     * Ensure the chunk id column is present in SELECT.
     * If columns are ['*'] or already include the id column (or alias), do nothing.
     * If selectRaw() was used, we append ", <idColumn> AS <idAlias>" to preserve raw select.
     *
     * @param string $idColumn e.g. "u.id" or "id"
     * @return void
     */
    protected function ensureChunkIdSelected($idColumn)
    {
        // If selecting all, id will be present anyway
        if ($this->columns === array('*')) {
            return;
        }

        $idKey = $this->stripAlias($idColumn); // typically "id"

        // Check if already selected
        foreach ($this->columns as $c) {
            if ($c instanceof RawExpr) {
                $expr = $c->get();

                // crude but practical checks:
                // contains " id" or ".id" or "id as"
                if (preg_match('/\b' . preg_quote($idKey, '/') . '\b/i', $expr)) {
                    return;
                }
            } else {
                $col = (string)$c;

                // allow "u.id", "id", or "u.id as id"
                if (strcasecmp($col, $idColumn) === 0) return;
                if (strcasecmp($col, $idKey) === 0) return;
                if (preg_match('/\b' . preg_quote($idKey, '/') . '\b/i', $col)) return;
            }
        }

        // Not found -> add it safely
        // If the user is using selectRaw(), we preserve their raw SELECT by appending raw.
        // We alias to the bare name ("id") so accessing $row['id'] works.
        $aliasSql = $idColumn . ' AS ' . $idKey;
        $this->columns[] = new RawExpr($aliasSql);
    }

    /**
     * Insert ignoring duplicates.
     * Supports single row (array/object) or bulk (array/iterable of rows).
     *
     * Returns:
     * - bool for single insert
     * - int inserted count for bulk
     *
     * @param array|object|\Traversable $data
     * @return bool|int
     */
    public function insertOrIgnore($data)
    {
        $driver = ConnectionManager::getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);;

        // Normalise to rows
        $bulk = $this->isBulkRows($data);
        $rows = $bulk ? $this->normaliseRows($data) : array($this->normaliseRow($data));

        // Remove empty rows
        $clean = array();
        foreach ($rows as $r) { if (!empty($r)) $clean[] = $r; }
        if (empty($clean)) return $bulk ? 0 : false;

        // Determine columns from first row
        $columns = array_keys($clean[0]);

        // Normalise each row to contain all columns
        $norm = array();
        foreach ($clean as $r) {
            $row = array();
            foreach ($columns as $c) {
                $row[$c] = array_key_exists($c, $r) ? $r[$c] : null;
            }
            $norm[] = $row;
        }

        // Build SQL prefix per driver
        // mysql:  INSERT IGNORE INTO ...
        // sqlite: INSERT OR IGNORE INTO ...
        // pgsql:  INSERT INTO ... ON CONFLICT DO NOTHING
        $prefix = 'INSERT INTO';
        if ($driver === 'mysql')  $prefix = 'INSERT IGNORE INTO';
        if ($driver === 'sqlite') $prefix = 'INSERT OR IGNORE INTO';

        $conn = ConnectionManager::getConnection();

        // batch insert (also avoids SQLite variable limits)
        $batchSize = 500;
        $chunks = array_chunk($norm, $batchSize);

        $inserted = 0;

        foreach ($chunks as $chunkRows) {
            $perRow = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $valuesSql = implode(',', array_fill(0, count($chunkRows), $perRow));

            $sql = $prefix . " {$this->table} (" . implode(',', $columns) . ") VALUES " . $valuesSql;

            $bindings = array();
            foreach ($chunkRows as $row) {
                foreach ($columns as $c) $bindings[] = $row[$c];
            }

            // Postgres needs ON CONFLICT DO NOTHING
            if ($driver === 'pgsql') {
                $sql .= " ON CONFLICT DO NOTHING";
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($bindings);

            $inserted += (int)$stmt->rowCount();
        }

        if ($bulk) return $inserted;
        return $inserted > 0;
    }

    /**
     * Upsert (insert or update on conflict).
     *
     * @param array|object|\Traversable $data Rows (bulk preferred)
     * @param array|string $uniqueBy Columns that define uniqueness / conflict target
     * @param array|null $updateColumns Columns to update (default: all except $uniqueBy)
     * @return int affected rows (best-effort; depends on driver)
     */
    public function upsert($data, $uniqueBy, $updateColumns = null)
    {
        $driver = ConnectionManager::getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);;

        $uniqueBy = is_array($uniqueBy) ? $uniqueBy : array($uniqueBy);

        // Normalise to rows
        $bulk = $this->isBulkRows($data);
        $rows = $bulk ? $this->normaliseRows($data) : array($this->normaliseRow($data));

        // Remove empty rows
        $clean = array();
        foreach ($rows as $r) { if (!empty($r)) $clean[] = $r; }
        if (empty($clean)) return 0;

        // Determine columns from first row
        $columns = array_keys($clean[0]);

        // Determine update columns
        if ($updateColumns === null) {
            $updateColumns = array();
            foreach ($columns as $c) {
                if (!in_array($c, $uniqueBy, true)) $updateColumns[] = $c;
            }
        }

        // Normalise rows to contain all columns
        $norm = array();
        foreach ($clean as $r) {
            $row = array();
            foreach ($columns as $c) {
                $row[$c] = array_key_exists($c, $r) ? $r[$c] : null;
            }
            $norm[] = $row;
        }

        // SQLite/Postgres require conflict target columns
        if (($driver === 'sqlite' || $driver === 'pgsql') && empty($uniqueBy)) {
            throw new \InvalidArgumentException('upsert() requires $uniqueBy for sqlite/pgsql.');
        }

        $conn = ConnectionManager::getConnection();

        // batch insert for safety (sqlite var limit)
        $batchSize = 300;
        $chunks = array_chunk($norm, $batchSize);

        $affected = 0;

        foreach ($chunks as $chunkRows) {
            $perRow = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $valuesSql = implode(',', array_fill(0, count($chunkRows), $perRow));

            $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES " . $valuesSql;

            $bindings = array();
            foreach ($chunkRows as $row) {
                foreach ($columns as $c) $bindings[] = $row[$c];
            }

            // Build per-driver upsert clause
            if ($driver === 'mysql') {
                // MySQL: ON DUPLICATE KEY UPDATE col = VALUES(col)
                $sets = array();
                foreach ($updateColumns as $c) {
                    // VALUES(col) is still supported in MySQL; if you later target strict MySQL 8.0.20+,
                    // we can switch to alias-based insert syntax.
                    $sets[] = "{$c} = VALUES({$c})";
                }
                // If no update columns, do a no-op update on a unique column to avoid syntax error
                if (empty($sets)) {
                    $noopCol = $uniqueBy[0];
                    $sets[] = "{$noopCol} = {$noopCol}";
                }

                $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $sets);
            }
            elseif ($driver === 'sqlite' || $driver === 'pgsql') {
                // SQLite/PG: ON CONFLICT (a,b) DO UPDATE SET col = excluded.col
                $conflict = implode(', ', $uniqueBy);

                if (empty($updateColumns)) {
                    $sql .= " ON CONFLICT ({$conflict}) DO NOTHING";
                } else {
                    $sets = array();
                    foreach ($updateColumns as $c) {
                        $sets[] = "{$c} = excluded.{$c}";
                    }
                    $sql .= " ON CONFLICT ({$conflict}) DO UPDATE SET " . implode(', ', $sets);
                }
            }
            else {
                // Fallback: try SQL standard like sqlite/pgsql
                $conflict = implode(', ', $uniqueBy);
                if (empty($conflict)) {
                    throw new \RuntimeException("upsert() not supported for driver '{$driver}' without uniqueBy.");
                }
                $sets = array();
                foreach ($updateColumns as $c) {
                    $sets[] = "{$c} = excluded.{$c}";
                }
                $sql .= empty($sets)
                    ? " ON CONFLICT ({$conflict}) DO NOTHING"
                    : " ON CONFLICT ({$conflict}) DO UPDATE SET " . implode(', ', $sets);
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($bindings);
            $affected += (int)$stmt->rowCount();
        }

        return $affected;
    }

}