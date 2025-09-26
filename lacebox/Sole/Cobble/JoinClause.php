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

class JoinClause
{
    /** @var string */
    public $type;
    /** @var string */
    public $table;
    /**
     * @var array<int, array{boolean:string,sql:string,bindings:array}>
     */
    public $conditions = [];

    public function __construct($type, $table) {
        $this->type  = strtoupper((string)$type);
        $this->table = (string)$table;
    }

    public function on($first, $operator, $second, $boolean = 'AND') {
        list($sql, $bindings) = $this->compileOn($first, $operator, $second);
        $this->conditions[] = [
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => $sql,
            'bindings' => $bindings
        ];
        return $this;
    }

    public function orOn($first, $operator, $second) {
        return $this->on($first, $operator, $second, 'OR');
    }

    public function onRaw($sql, $bindings = [], $boolean = 'AND') {
        $this->conditions[] = [
            'boolean'  => strtoupper((string)$boolean),
            'sql'      => '(' . $sql . ')',
            'bindings' => (array)$bindings
        ];
        return $this;
    }

    protected function compileOn($first, $operator, $second) {
        $left = ($first instanceof RawExpr) ? $first->get() : (string)$first;

        $isColLike = ($second instanceof RawExpr)
            || (is_string($second) && preg_match('/^[`]?[\w]+([.`][\w`]+)*$/', $second));

        if ($second instanceof RawExpr) {
            return array($left . ' ' . $operator . ' ' . $second->get(), array());
        }

        if ($isColLike) {
            return array($left . ' ' . $operator . ' ' . (string)$second, array());
        }

        return array($left . ' ' . $operator . ' ?', array($second));
    }

    public function toSqlAndBindings() {

        if (empty($this->conditions)) {
            return array($this->type . ' JOIN ' . $this->table, array());
        }

        $sql = $this->type . ' JOIN ' . $this->table . ' ON ';
        $bindings = array();

        $parts = array();
        foreach ($this->conditions as $i => $c) {
            $prefix = ($i === 0) ? '' : ' ' . $c['boolean'] . ' ';
            $parts[] = $prefix . $c['sql'];
            $bindings = array_merge($bindings, $c['bindings']);
        }
        $sql .= implode('', $parts);

        return array($sql, $bindings);
    }
}