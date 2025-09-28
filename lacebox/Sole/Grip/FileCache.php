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


namespace Lacebox\Sole\Grip;

use Lacebox\Shoelace\CacheInterface;
use DateInterval;
use DateTimeImmutable;

class FileCache implements CacheInterface
{
    protected $dir;
    protected $defaultTtl;

    // ── NEW: GC tuning (tweak via config('cache.*') if you like) ─────────────
    protected $sweepProbabilityPerThousand = 2;  // ≈0.2% chance on set()
    protected $sweepBatch                   = 200; // max files per sweep
    protected $sweepMaxSeconds              = 2;   // time cap per sweep (seconds)
    protected $tmpTtlSeconds                = 600; // delete tmp files older than 10 min

    /**
     * @param string|null $dir
     * @param int|null    $defaultTtl
     */
    public function __construct($dir = null, $defaultTtl = null)
    {
        $this->dir = $dir ?: sys_get_temp_dir() . '/lacephp_cache';
        $this->defaultTtl = $defaultTtl !== null ? (int)$defaultTtl : 3600;

        // Optional: pull GC knobs from config if present
        if (function_exists('config')) {
            $cfg = (array) config('cache');
            if (isset($cfg['file_sweep_probability'])) $this->sweepProbabilityPerThousand = (int)$cfg['file_sweep_probability'];
            if (isset($cfg['file_sweep_batch']))       $this->sweepBatch = (int)$cfg['file_sweep_batch'];
            if (isset($cfg['file_sweep_seconds']))     $this->sweepMaxSeconds = (int)$cfg['file_sweep_seconds'];
        }

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0777, true);
        }

        $this->ensureDir($this->dir);
    }

    public function get($key, $default = null)
    {
        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return $default;
        }

        $data = @file_get_contents($path);
        if ($data === false) {
            return $default;
        }

        $decoded = @unserialize($data);
        if (!is_array($decoded) || !array_key_exists('e', $decoded) || !array_key_exists('v', $decoded)) {
            return $default;
        }

        $expires = (int)$decoded['e'];
        if ($expires !== 0 && $expires < time()) {
            @unlink($path);
            return $default;
        }

        return $decoded['v'];
    }

    public function set($key, $value, $ttl = null)
    {
        $ttl = $this->normalizeTtl($ttl);
        $expires = $ttl === 0 ? 0 : time() + $ttl;

        $payload = serialize(array('e' => $expires, 'v' => $value));
        $tmp = $this->pathFor($key) . '.' . uniqid('tmp', true);
        if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
            return false;
        }
        return @rename($tmp, $this->pathFor($key));
    }

    public function delete($key)
    {
        $path = $this->pathFor($key);
        return is_file($path) ? @unlink($path) : true;
    }

    public function clear()
    {
        if (!is_dir($this->dir)) return true;
        $ok = true;
        $it = @scandir($this->dir);
        if ($it === false) return false;
        foreach ($it as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $this->dir . '/' . $f;
            if (is_file($p) && !@unlink($p)) $ok = false;
        }
        return $ok;
    }

    public function has($key)
    {
        return $this->get($key, '__MISS__') !== '__MISS__';
    }

    public function increment($key, $by = 1)
    {
        $value = (int)$this->get($key, 0) + (int)$by;
        $this->set($key, $value, 0);
        return $value;
    }

    public function decrement($key, $by = 1)
    {
        $value = (int)$this->get($key, 0) - (int)$by;
        $this->set($key, $value, 0);
        return $value;
    }

    public function remember($key, $ttl, $callback)
    {
        $val = $this->get($key, '__MISS__');
        if ($val !== '__MISS__') return $val;

        $val = call_user_func($callback);
        $this->set($key, $val, $ttl);
        return $val;
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    protected function pathFor($key)
    {
        $hash = sha1((string)$key);
        return $this->dir . '/' . $hash . '.cache';
    }

    protected function normalizeTtl($ttl)
    {
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable('now');
            $ttl = $now->add($ttl)->getTimestamp() - $now->getTimestamp();
        }
        if ($ttl === null) return (int)$this->defaultTtl;
        $ttl = (int)$ttl;
        return $ttl < 0 ? 0 : $ttl;
    }

    // ── NEW: Public pruning API (safe to call from CLI/cron) ──────────────────
    /**
     * Remove expired cache files and old temp files.
     *
     * @param int|null $maxFiles    Stop after this many files (default: sweepBatch)
     * @param int|null $maxSeconds  Time cap (default: sweepMaxSeconds)
     * @return int                  Number of files removed
     */
    public function purgeExpired($maxFiles = null, $maxSeconds = null)
    {
        $maxFiles   = $maxFiles   !== null ? (int)$maxFiles   : $this->sweepBatch;
        $maxSeconds = $maxSeconds !== null ? (int)$maxSeconds : $this->sweepMaxSeconds;

        $start = microtime(true);
        $removed = 0;

        $list = @scandir($this->dir);
        if ($list === false) return 0;

        foreach ($list as $f) {
            if ($f === '.' || $f === '..') continue;

            $p = $this->dir . '/' . $f;

            // Clean .tmp files older than tmpTtlSeconds
            if (substr($f, -4) === '.tmp') {
                if (@filemtime($p) < (time() - $this->tmpTtlSeconds)) {
                    if (@unlink($p)) $removed++;
                }
                continue;
            }

            // Only consider .cache files
            if (substr($f, -6) !== '.cache') continue;

            $data = @file_get_contents($p);
            if ($data === false) continue;

            $decoded = @unserialize($data);
            if (!is_array($decoded) || !array_key_exists('e', $decoded)) {
                // If unreadable payload, remove it to be safe
                if (@unlink($p)) $removed++;
                continue;
            }

            $expires = (int)$decoded['e'];
            if ($expires !== 0 && $expires < time()) {
                if (@unlink($p)) $removed++;
            }

            if ($removed >= $maxFiles) break;
            if ((microtime(true) - $start) >= $maxSeconds) break;
        }

        return $removed;
    }

    protected function ensureDir($dir)
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_file($dir . '/.htaccess')) {
            @file_put_contents($dir . '/.htaccess', "Deny from all\n");
        }
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', "");
        }
    }
}