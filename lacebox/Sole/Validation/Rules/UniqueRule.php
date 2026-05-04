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

namespace Lacebox\Sole\Validation\Rules;

use Lacebox\Shoelace\RuleInterface;
use Lacebox\Sole\Cobble\QueryBuilder;

class UniqueRule implements RuleInterface
{
    protected $table;
    protected $column;
    protected $ignoreValue;
    protected $ignoreColumn = 'id';
    protected $field;

    /**
     * unique:table,column,ignoreValue,ignoreColumn
     * Examples:
     * - unique:users,email
     * - unique:users,email,12,id
     */
    public function __construct(string $table, ?string $column = null, $ignoreValue = null, ?string $ignoreColumn = null)
    {
        $this->table       = trim($table);
        $this->column      = $column ? trim($column) : null;
        $this->ignoreValue = $ignoreValue;
        if ($ignoreColumn) {
            $this->ignoreColumn = trim($ignoreColumn);
        }
    }

    public function setField(string $field): void
    {
        $this->field = $field;
        if ($this->column === null || $this->column === '') {
            $this->column = $field; // default column = field name
        }
    }

    public function validate($value, array $data): bool
    {
        // let required handle missing
        if ($value === null || $value === '') return true;

        $col = $this->column ?: ($this->field ?: 'id');

        $qb = QueryBuilder::table($this->table)->where($col, '=', $value);

        if ($this->ignoreValue !== null && $this->ignoreValue !== '') {
            $qb->where($this->ignoreColumn, '!=', $this->ignoreValue);
        }

        // unique passes when NO row exists
        return $qb->doesntExist();
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} has already been taken.";
    }
}