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

class ExistsRule implements RuleInterface
{
    protected $table;
    protected $column;
    protected $field;

    public function __construct(string $table, ?string $column = null)
    {
        $this->table  = trim($table);
        $this->column = $column ? trim($column) : null;
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

        return QueryBuilder::table($this->table)
            ->where($col, '=', $value)
            ->exists();
    }

    public function message(): string
    {
        $f = $this->field ?? 'field';
        return "{$f} does not exist.";
    }
}