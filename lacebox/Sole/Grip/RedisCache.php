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

class RedisCache implements CacheInterface
{
    /** @var \Redis|null */
    protected $redis;
    protected $prefix;
    protected $defaultTtl;

    /**
     * @param array $config host, port, auth, db, prefix, default_ttl
     */
    public function __construct(array $config = array())
    {
        if (!class_exists('Redis')) {
            throw new \RuntimeException('Redis extension not installed');
        }

        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? (int)$config['port'] : 6379;
        $auth = isset($config['auth']) ? $config['auth'] : null;
        $db   = isset($config['db'])   ? (int)$config['db']   : 0;
        $this->prefix = isset($config['prefix']) ? $config['prefix'] : 'lace:';
        $this->defaultTtl = isset($config['default_ttl']) ? (int)$config['default_ttl'] : 3600;

        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
        if ($auth) $this->redis->auth($auth);
        if ($db)   $this->redis->select($db);
    }

    public function get($key, $default = null)
    {
        $raw = $this->redis->get($this->prefix.$key);
        if ($raw === false || $raw === null) return $default;

        $val = @unserialize($raw);
        return $val === false && $raw !== serialize(false) ? $default : $val;
    }

    public function set($key, $value, $ttl = null)
    {
        $ttl = $this->normalizeTtl($ttl);
        $raw = serialize($value);
        if ($ttl === 0) {
            return (bool)$this->redis->set($this->prefix.$key, $raw);
        }
        return (bool)$this->redis->setex($this->prefix.$key, $ttl, $raw);
    }

    public function delete($key)
    {
        return (bool)$this->redis->del($this->prefix.$key);
    }

    public function clear()
    {
        // Clearing only our namespace (SCAN + DEL) to be safe
        $it = null;
        $pattern = $this->prefix . '*';
        while (true) {
            $keys = $this->redis->scan($it, $pattern, 1000);
            if ($keys === false) break;
            if (!empty($keys)) $this->redis->del($keys);
        }
        return true;
    }

    public function has($key)
    {
        return (bool)$this->redis->exists($this->prefix.$key);
    }

    public function increment($key, $by = 1)
    {
        return (int)$this->redis->incrBy($this->prefix.$key, (int)$by);
    }

    public function decrement($key, $by = 1)
    {
        return (int)$this->redis->decrBy($this->prefix.$key, (int)$by);
    }

    public function remember($key, $ttl, $callback)
    {
        $val = $this->get($key, '__MISS__');
        if ($val !== '__MISS__') return $val;

        $val = call_user_func($callback);
        $this->set($key, $val, $ttl);
        return $val;
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