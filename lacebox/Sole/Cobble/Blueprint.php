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

class Blueprint
{
    public $table;
    public $columns = [];
    public $indexes = [];
    public $drops   = [];
    public $renames = [];

    public $foreigns = [];      // list of FK definitions to ADD
    public $dropForeigns = [];  // list of FK names to DROP
    public $dropIndexes = [];   // list of index names to DROP
    public $dropPrimary = false; // optional PK drop (MySQL) / needs name for PG

    public $tableRename = null;  // target name if table is to be renamed
    public $renameIndexes = []; // [ [from=>..., to=>...], ... ]

    protected $currentForeign = null;

    /** @var int|null Index of the last added column for chaining */
    protected $current = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    //
    // ─── COLUMN TYPES ────────────────────────────────────────────────────────────
    //

    public function increments(string $col): self
    {
        $this->columns[] = [
            'type'    => 'int',
            'name'    => $col,
            'auto'    => true,
            'primary' => true,
            'unsigned'=> true,
            'nullable'=> false,
        ];
        $this->current = count($this->columns) - 1;
        return $this;
    }

    public function integer(string $col, bool $auto = false, bool $unsigned = false): self
    {
        $this->columns[] = [
            'type'     => 'int',
            'name'     => $col,
            'auto'     => $auto,
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        $this->current = count($this->columns) - 1;
        return $this;
    }

    public function tinyInteger(string $col, bool $unsigned = false): self
    {
        return $this->addSimpleType('tinyint', $col, ['unsigned' => $unsigned, 'nullable'=>false]);
    }

    public function smallInteger(string $col, bool $unsigned = false): self
    {
        return $this->addSimpleType('smallint', $col, ['unsigned' => $unsigned, 'nullable'=>false]);
    }

    public function bigInteger(string $col, bool $auto = false, bool $unsigned = false): self
    {
        return $this->addSimpleType('bigint', $col, ['auto' => $auto, 'unsigned' => $unsigned, 'nullable'=>false]);
    }

    public function boolean(string $col): self
    {
        return $this->addSimpleType('tinyint', $col, ['length'=>1, 'nullable'=>false]);
    }

    public function string(string $col, int $length = 255): self
    {
        return $this->addSimpleType('varchar', $col, ['length' => $length, 'nullable'=>false]);
    }

    public function text(string $col): self
    {
        return $this->addSimpleType('text', $col, ['nullable'=>false]);
    }

    public function mediumText(string $col): self
    {
        return $this->addSimpleType('mediumtext', $col, ['nullable'=>false]);
    }

    public function longText(string $col): self
    {
        return $this->addSimpleType('longtext', $col, ['nullable'=>false]);
    }

    public function float(string $col, int $total = 8, int $places = 2): self
    {
        return $this->addSimpleType('float', $col, ['precision' => $total, 'scale' => $places, 'nullable'=>false]);
    }

    public function double(string $col, int $total = 8, int $places = 2): self
    {
        return $this->addSimpleType('double', $col, ['precision' => $total, 'scale' => $places, 'nullable'=>false]);
    }

    public function decimal(string $col, int $total = 8, int $places = 2): self
    {
        return $this->addSimpleType('decimal', $col, ['precision' => $total, 'scale' => $places, 'nullable'=>false]);
    }

    public function enum(string $col, array $allowed): self
    {
        // store allowed values; other chainers (default, nullable, unique, etc.) still work
        return $this->addSimpleType('enum', $col, ['allowed' => $allowed, 'nullable' => false]);
    }

    public function date(string $col): self
    {
        return $this->addSimpleType('date', $col, ['nullable'=>false]);
    }

    public function dateTime(string $col, int $precision = 0): self
    {
        return $this->addSimpleType('datetime', $col, ['precision' => $precision, 'nullable'=>false]);
    }

    public function time(string $col, int $precision = 0): self
    {
        return $this->addSimpleType('time', $col, ['precision' => $precision, 'nullable'=>false]);
    }

    public function timestamp(string $col, int $precision = 0): self
    {
        return $this->addSimpleType('timestamp', $col, ['precision' => $precision, 'nullable'=>false]);
    }

    public function softDeletes(string $col = 'deleted_at'): self
    {
        $this->addSimpleType('timestamp', $col, ['nullable' => true]);
        return $this;
    }

    public function timestamps(): self
    {
        $this->addSimpleType('timestamp', 'created_at', ['nullable' => true]);
        $this->addSimpleType('timestamp', 'updated_at', ['nullable' => true]);
        return $this;
    }

    public function json(string $col): self
    {
        return $this->addSimpleType('json', $col, ['nullable'=>false]);
    }

    //
    // ─── CHAINABLE PER-COLUMN OPTIONS (apply to last added column) ───────────────
    //

    public function default($value): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['default'] = $value;
            unset($this->columns[$this->current]['defaultRaw']);
        }
        return $this;
    }

    public function defaultRaw(string $expression): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['defaultRaw'] = $expression;
            unset($this->columns[$this->current]['default']);
        }
        return $this;
    }

    public function nullable(bool $bool = true): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['nullable'] = (bool) $bool;
        }
        return $this;
    }

    public function unsigned(bool $bool = true): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['unsigned'] = (bool) $bool;
        }
        return $this;
    }

    public function unique(bool $bool = true): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['unique'] = (bool) $bool;
        }
        return $this;
    }

    //
    // ─── INDEX & KEY DEFINITIONS ─────────────────────────────────────────────────
    //

    public function primary($columns, string $name = null): self
    {
        $this->indexes[] = ['type'=>'primary','columns'=> (array)$columns,'name'=>$name];
        return $this;
    }

    public function uniqueIndex($columns, string $name = null): self
    {
        // avoid name clash with unique() chainable above
        $this->indexes[] = ['type'=>'unique','columns'=> (array)$columns,'name'=>$name];
        return $this;
    }

    public function index($columns, string $name = null): self
    {
        $this->indexes[] = ['type'=>'index','columns'=> (array)$columns,'name'=>$name];
        return $this;
    }

    //
    // ─── COLUMN REMOVAL & RENAMING ────────────────────────────────────────────────
    //

    public function dropColumn(string $col): self
    {
        $this->drops[] = $col;
        return $this;
    }

    public function renameColumn(string $from, string $to): self
    {
        $this->renames[] = ['from'=>$from,'to'=>$to];
        return $this;
    }

    // ── Foreign key chainers ─────────────────────────────────────────────
    /**
     * Begin a foreign key definition. $columns can be string or array.
     * $name is optional; if null we'll auto-name later.
     */
    public function foreign($columns, string $name = null): self
    {
        $def = [
            'name'      => $name,                 // optional
            'columns'   => (array)$columns,
            'refTable'  => null,
            'refCols'   => null,
            'onDelete'  => null,
            'onUpdate'  => null,
        ];
        $this->foreigns[] = $def;
        $this->currentForeign = count($this->foreigns) - 1;
        return $this;
    }

    /** Set referenced columns */
    public function references($columns): self
    {
        if ($this->currentForeign !== null) {
            $this->foreigns[$this->currentForeign]['refCols'] = (array)$columns;
        }
        return $this;
    }

    /** Set referenced table */
    public function on(string $table): self
    {
        if ($this->currentForeign !== null) {
            $this->foreigns[$this->currentForeign]['refTable'] = $table;
        }
        return $this;
    }

    /** ON DELETE action: RESTRICT|CASCADE|SET NULL|NO ACTION */
    public function onDelete(string $action): self
    {
        if ($this->currentForeign !== null) {
            $this->foreigns[$this->currentForeign]['onDelete'] = strtoupper($action);
        }
        return $this;
    }

    /** ON UPDATE action: RESTRICT|CASCADE|SET NULL|NO ACTION */
    public function onUpdate(string $action): self
    {
        if ($this->currentForeign !== null) {
            $this->foreigns[$this->currentForeign]['onUpdate'] = strtoupper($action);
        }
        return $this;
    }

// ── Drops ────────────────────────────────────────────────────────────

    /** Drop a foreign key by name */
    public function dropForeign(string $name): self
    {
        $this->dropForeigns[] = $name;
        return $this;
    }

    /** Drop an index by name (non-unique or unique) */
    public function dropIndex(string $name): self
    {
        $this->dropIndexes[] = $name;
        return $this;
    }

    /** Drop unique index by name (alias of dropIndex) */
    public function dropUnique(string $name): self
    {
        $this->dropIndexes[] = $name;
        return $this;
    }

    /** Rename the current table to $to */
    public function renameTable(string $to): self
    {
        $this->tableRename = $to;
        return $this;
    }

    /** Rename an index from $from to $to (portable: PG/MySQL native; SQLite -> drop/create) */
    public function renameIndex(string $from, string $to): self
    {
        $this->renameIndexes[] = ['from' => $from, 'to' => $to];
        return $this;
    }

    /** Drop primary key (MySQL). For PostgreSQL you must specify its name via dropPrimaryNamed(). */
    public function dropPrimary(): self
    {
        $this->dropPrimary = true;
        return $this;
    }

    //
    // ─── INTERNAL HELPERS ─────────────────────────────────────────────────────────
    //

    public function precision(int $total, int $places = 0): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['precision'] = $total;
            $this->columns[$this->current]['scale']     = $places;
        }
        return $this;
    }

    public function scale(int $places): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['scale'] = $places;
        }
        return $this;
    }

    /**
     * Mark the last added column as a "modify" operation instead of "add".
     * Usage: $t->string('name', 100)->nullable()->change();
     */
    public function change(): self
    {
        if ($this->current !== null) {
            $this->columns[$this->current]['__op'] = 'modify';
        }
        return $this;
    }

    /** catch-all for simple types */
    protected function addSimpleType(string $type, string $col, array $options = []): self
    {
        $this->columns[] = array_merge($options, [
            'type'  => $type,
            'name'  => $col,
        ]);
        $this->current = count($this->columns) - 1;
        return $this;
    }
}