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
    protected static $table = null;

    /** If empty, we treat ALL attributes as fillable. */
    protected $fillable = [];

    /** Columns that must NEVER be mass-assigned automatically. */
    protected $guarded  = ['id', 'created_at', 'updated_at'];

    protected $attributes = [];
    protected $original   = [];
    protected $exists     = false;
    protected $with       = [];

    public function __construct(array $attrs = [], bool $exists = false)
    {
        $this->fill($attrs);
        $this->original = $this->attributes;
        $this->exists   = $exists;
    }

    /** Mass-assign attributes (respect guarded if fillable is empty). */
    public function fill(array $attrs): self
    {
        foreach ($attrs as $k => $v) {
            $this->attributes[$k] = $v;
        }
        return $this;
    }

    public static function query(): QueryBuilder
    {
        return QueryBuilder::table(static::getTable())->asClass(static::class);
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find($id): ?self
    {
        return static::query()->where('id', '=', $id)->first();
    }

    /**
     * Hook: let child models prepare data before saving (e.g., set defaults).
     * Return false to abort save.
     */
    protected function beforeSave(): bool
    {
        return true;
    }

    /**
     * Collect data to persist.
     * - If $fillable is non-empty, use only those keys that exist in attributes.
     * - If $fillable is empty, use ALL attributes EXCEPT guarded.
     */
    protected function buildPersistableData(): array
    {
        $data = [];

        if (!empty($this->fillable)) {
            foreach ($this->fillable as $col) {
                if (array_key_exists($col, $this->attributes)) {
                    $data[$col] = $this->attributes[$col];
                }
            }
            return $data;
        }

        // fillable empty → take all attributes except guarded
        foreach ($this->attributes as $k => $v) {
            if (!in_array($k, $this->guarded, true)) {
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * Insert or update as needed, and refresh this instance.
     * @return $this
     */
    public function save(): self
    {
        // Pre-save hook (e.g., generate defaults)
        if ($this->beforeSave() === false) {
            return $this;
        }

        $data = $this->buildPersistableData();

        if ($this->exists) {
            // Only update changed ("dirty") fields
            $dirty = array();
            foreach ($data as $k => $v) {
                if (!array_key_exists($k, $this->original) || $this->original[$k] !== $v) {
                    $dirty[$k] = $v;
                }
            }

            if (!empty($dirty)) {
                static::query()->where('id', '=', $this->attributes['id'])->update($dirty);
            }
        } else {
            // Insert requires that all NOT NULL columns without DB defaults be present
            // (buildPersistableData() now includes everything unless guarded)
            $id = static::query()->insertGetId($data);
            $this->attributes['id'] = $id;
            $this->exists = true;
        }

        return $this->refresh();
    }

    public function refresh()
    {
        if (!$this->exists || !isset($this->attributes['id'])) {
            return $this;
        }
        $fresh = static::query()->where('id', '=', $this->attributes['id'])->first();

        if ($fresh) {
            // Pull raw arrays off the re-hydrated model to avoid recursion
            $this->attributes = $fresh->attributes;
            $this->original   = $fresh->original;
        }

        return $this;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        static::query()->where('id', '=', $this->attributes['id'])->delete();
        $this->exists = false;
        return true;
    }

    public function __get($key)
    {
        if (in_array($key, $this->with, true) && method_exists($this, $key)) {
            return $this->$key();
        }
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $val)
    {
        $this->attributes[$key] = $val;
    }

    public function with(array $relations): self
    {
        $this->with = $relations;
        return $this;
    }

    protected static function getTable(): string
    {
        if (static::$table) {
            return static::$table;
        }
        $short = (new \ReflectionClass(static::class))->getShortName();
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $short));
        return $snake . 's';
    }

    // ─── Relation helpers (unchanged) ─────────────────────────────────────────

    protected function belongsTo(string $related, string $foreignKey)
    {
        $fk = $this->attributes[$foreignKey] ?? null;
        return $fk ? $related::find($fk) : null;
    }

    protected function hasMany(string $related, string $foreignKey): array
    {
        $rows = $related::query()->where($foreignKey, '=', $this->attributes['id'])->get();
        return array_map(function(array $r) use ($related) { return new $related($r, true); }, $rows);
    }

    protected function hasOne(string $related, string $foreignKey)
    {
        $row = $related::query()->where($foreignKey, '=', $this->attributes['id'])->first();
        return $row ? new $related($row, true) : null;
    }

    protected function belongsToMany(string $related, string $pivotTable, string $foreignKey, string $otherKey): array
    {
        $qb  = QueryBuilder::table($pivotTable)->select([$otherKey])->where($foreignKey, '=', $this->attributes['id']);
        $ids = array_column($qb->get(), $otherKey);
        return array_map(function($id) use ($related) { return $related::find($id); }, $ids);
    }
}