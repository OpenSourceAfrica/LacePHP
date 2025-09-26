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

    /**
     * @param string|null $dir
     * @param int|null    $defaultTtl
     */
    public function __construct($dir = null, $defaultTtl = null)
    {
        $this->dir = $dir ?: sys_get_temp_dir() . '/lacephp_cache';
        $this->defaultTtl = $defaultTtl !== null ? (int)$defaultTtl : 3600;

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0777, true);
        }
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
}