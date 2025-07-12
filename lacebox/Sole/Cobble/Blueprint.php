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
        ];
        return $this;
    }

    public function integer(string $col, bool $auto = false, bool $unsigned = false): self
    {
        $this->columns[] = [
            'type'     => 'int',
            'name'     => $col,
            'auto'     => $auto,
            'unsigned' => $unsigned,
        ];
        return $this;
    }

    public function tinyInteger(string $col, bool $unsigned = false): self
    {
        return $this->addSimpleType('tinyint', $col, ['unsigned' => $unsigned]);
    }

    public function smallInteger(string $col, bool $unsigned = false): self
    {
        return $this->addSimpleType('smallint', $col, ['unsigned' => $unsigned]);
    }

    public function bigInteger(string $col, bool $auto = false, bool $unsigned = false): self
    {
        return $this->addSimpleType('bigint', $col, ['auto' => $auto, 'unsigned' => $unsigned]);
    }

    public function boolean(string $col): self
    {
        return $this->addSimpleType('tinyint', $col, ['length'=>1]);
    }

    public function string(string $col, int $length = 255): self
    {
        return $this->addSimpleType('varchar', $col, ['length' => $length]);
    }

    public function text(string $col): self
    {
        return $this->addSimpleType('text', $col);
    }

    public function mediumText(string $col): self
    {
        return $this->addSimpleType('mediumtext', $col);
    }

    public function longText(string $col): self
    {
        return $this->addSimpleType('longtext', $col);
    }

    public function float(string $col, int $total = 8, int $places = 2): self
    {
        return $this->addSimpleType('float', $col, ['precision' => $total, 'scale' => $places]);
    }

    public function double(string $col, int $total = 8, int $places = 2): self
    {
        return $this->addSimpleType('double', $col, ['precision' => $total, 'scale' => $places]);
    }

    public function decimal(string $col, int $total = 8, int $places = 2): self
    {
        return $this->addSimpleType('decimal', $col, ['precision' => $total, 'scale' => $places]);
    }

    public function date(string $col): self
    {
        return $this->addSimpleType('date', $col);
    }

    public function dateTime(string $col, int $precision = 0): self
    {
        return $this->addSimpleType('datetime', $col, ['precision' => $precision]);
    }

    public function time(string $col, int $precision = 0): self
    {
        return $this->addSimpleType('time', $col, ['precision' => $precision]);
    }

    public function timestamp(string $col, int $precision = 0): self
    {
        return $this->addSimpleType('timestamp', $col, ['precision' => $precision]);
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
        return $this->addSimpleType('json', $col);
    }

    //
    // ─── INDEX & KEY DEFINITIONS ─────────────────────────────────────────────────
    //

    public function primary($columns, string $name = null): self
    {
        $this->indexes[] = ['type'=>'primary','columns'=> (array)$columns,'name'=>$name];
        return $this;
    }

    public function unique($columns, string $name = null): self
    {
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

    //
    // ─── INTERNAL HELPERS ─────────────────────────────────────────────────────────
    //

    /** catch-all for simple types */
    protected function addSimpleType(
        string $type,
        string $col,
        array  $options = []
    ): self {
        $this->columns[] = array_merge($options, [
            'type'  => $type,
            'name'  => $col,
        ]);
        return $this;
    }
}