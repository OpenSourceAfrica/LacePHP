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

namespace Lacebox\Sole;

class MetricsCollector
{
    /** @var array<string,int> Counter metrics */
    protected static $counters = [];

    /** @var array<string,float[]> Timing histograms */
    protected static $timings = [];

    /** Path to metrics storage */
    protected static $storageFile = __DIR__ . '/../../shoebox/metrics/metrics.json';

    /** Load existing metrics from disk */
    protected static function load(): void
    {
        if (! file_exists(self::$storageFile)) {
            return;
        }
        $json = json_decode(file_get_contents(self::$storageFile), true);
        if (isset($json['counters']) && is_array($json['counters'])) {
            self::$counters = $json['counters'];
        }
        if (isset($json['timings']) && is_array($json['timings'])) {
            self::$timings = $json['timings'];
        }
    }

    /** Save current metrics to disk */
    protected static function save(): void
    {
        $dir = dirname(self::$storageFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::$storageFile, json_encode([
            'counters' => self::$counters,
            'timings'  => self::$timings,
        ], JSON_PRETTY_PRINT));
    }

    /** Increment a counter by $by (default 1) */
    public static function increment(string $name, int $by = 1): void
    {
        self::load();
        if (! isset(self::$counters[$name])) {
            self::$counters[$name] = 0;
        }
        self::$counters[$name] += $by;
        self::save();
    }

    /** Record a timing (in milliseconds) */
    public static function recordTiming(string $name, float $ms): void
    {
        self::load();
        if (! isset(self::$timings[$name])) {
            self::$timings[$name] = [];
        }
        self::$timings[$name][] = $ms;
        self::save();
    }

    /** Return all counters */
    public static function getCounters(): array
    {
        self::load();
        return self::$counters;
    }

    /** Return aggregated timing stats: count, min, max, avg */
    public static function getTimings(): array
    {
        self::load();
        $out = [];
        foreach (self::$timings as $key => $arr) {
            $cnt = count($arr);
            $min = $cnt ? min($arr) : 0;
            $max = $cnt ? max($arr) : 0;
            $avg = $cnt ? array_sum($arr) / $cnt : 0;
            $out[$key] = compact('cnt','min','max','avg');
        }
        return $out;
    }
}