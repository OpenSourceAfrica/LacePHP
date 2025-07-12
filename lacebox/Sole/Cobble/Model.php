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

abstract class Model
{
    /** @var string|null  Override if your table name isn’t the plural of the class */
    protected static $table = null;

    /** @var array  Which attributes may be mass-assigned */
    protected $fillable = [];

    /** @var array  Holds current attributes */
    protected $attributes = [];

    /** @var array  Tracks original values for change detection */
    protected $original = [];

    /** @var bool   Has this record been persisted? */
    protected $exists = false;

    /** @var array  Relations to eager-load */
    protected $with = [];

    /**
     * Construct with initial data.
     * @param array $attrs  Column→value map
     * @param bool  $exists If true, indicates a pre-existing row
     */
    public function __construct(array $attrs = [], bool $exists = false)
    {
        $this->attributes = $attrs;
        $this->original   = $attrs;
        $this->exists     = $exists;
    }

    /** Start a new query for this model’s table */
    public static function query(): QueryBuilder
    {
        return QueryBuilder::table(static::getTable())
            ->asClass(static::class);
    }

    /** Fetch all rows */
    public static function all(): array
    {
        return static::query()->get();
    }

    /** Find by primary key (assumed `id`) */
    public static function find($id): ?self
    {
        return static::query()
            ->where('id','=', $id)
            ->first();
    }

    /**
     * Insert or update as needed, and refresh this instance.
     *
     * @return $this
     */
    public function save(): self
    {
        // 1) mass-assignable data only
        $data = [];
        foreach ($this->fillable as $col) {
            if (array_key_exists($col, $this->attributes)) {
                $data[$col] = $this->attributes[$col];
            }
        }

        if ($this->exists) {
            // 2a) update: only the changed ("dirty") fields
            $dirty = array_filter($data, function($v, $k) {
                return !array_key_exists($k, $this->original)
                    || $this->original[$k] !== $v;
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($dirty)) {
                static::query()
                    ->where('id', '=', $this->attributes['id'])
                    ->update($dirty);
            }

        } else {
            // 2b) insert: get the new ID, mark as existing
            $id = static::query()->insertGetId($data);
            $this->attributes['id'] = $id;
            $this->exists = true;
        }

        // 3) reload fresh data (incl. any defaults or triggers)
        return $this->refresh();
    }

    /**
     * Reload this model’s data from the database.
     * @return $this
     */
    public function refresh()
    {
        if (! $this->exists || ! isset($this->attributes['id'])) {
            return $this;
        }
        $fresh = static::query()
            ->where('id', '=', $this->attributes['id'])
            ->first();

        if ($fresh) {
            // Overwrite attributes & original tracking
            $this->attributes = $fresh->attributes;
            $this->original   = $fresh->original;
        }

        return $this;
    }

    /** Delete this record (hard delete) */
    public function delete(): bool
    {
        if (! $this->exists) {
            return false;
        }
        static::query()
            ->where('id', '=', $this->attributes['id'])
            ->delete();
        $this->exists = false;
        return true;
    }

    /** Magic getter: attribute or relation */
    public function __get($key)
    {
        // Eager‐load if requested
        if (in_array($key, $this->with) && method_exists($this, $key)) {
            return $this->$key();
        }
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $val)
    {
        $this->attributes[$key] = $val;
    }

    /** Eagerly load relations before returning */
    public function with(array $relations): self
    {
        $this->with = $relations;
        return $this;
    }

    /** Derive table name from class or override */
    protected static function getTable(): string
    {
        if (static::$table) {
            return static::$table;
        }
        $short = (new \ReflectionClass(static::class))->getShortName();
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $short));
        return $snake . 's';
    }

    //
    // ─── RELATION HELPERS ─────────────────────────────────────────────────────────
    //

    /** Belongs to one other model */
    protected function belongsTo(string $related, string $foreignKey)
    {
        $fk = $this->attributes[$foreignKey] ?? null;
        return $fk ? $related::find($fk) : null;
    }

    /** Has many of another model */
    protected function hasMany(string $related, string $foreignKey): array
    {
        $rows = $related::query()
            ->where($foreignKey, '=', $this->attributes['id'])
            ->get();

        // traditional closure
        return array_map(function(array $r) use ($related) {
            return new $related($r, true);
        }, $rows);
    }

    /** Has one other model */
    protected function hasOne(string $related, string $foreignKey)
    {
        $row = $related::query()->where($foreignKey, '=', $this->attributes['id'])->first();
        return $row ? new $related($row, true) : null;
    }

    /** Many-to-many through pivot table */
    protected function belongsToMany(
        string $related,
        string $pivotTable,
        string $foreignKey,
        string $otherKey
    ): array {
        $qb  = QueryBuilder::table($pivotTable)
            ->select([$otherKey])
            ->where($foreignKey, '=', $this->attributes['id']);
        $ids = array_column($qb->get(), $otherKey);

        // again, no arrow fn()
        return array_map(function($id) use ($related) {
            return $related::find($id);
        }, $ids);
    }
}