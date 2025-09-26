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

/**
 * Supports either Memcached or Memcache extension.
 */
class MemcacheCache implements CacheInterface
{
    /** @var \Memcached|\Memcache|null */
    protected $client;
    protected $useMemcached = false;
    protected $prefix;
    protected $defaultTtl;

    /**
     * @param array $config servers => [[host,port,weight]], prefix, default_ttl
     */
    public function __construct(array $config = array())
    {
        $this->prefix = isset($config['prefix']) ? $config['prefix'] : 'lace:';
        $this->defaultTtl = isset($config['default_ttl']) ? (int)$config['default_ttl'] : 3600;

        if (class_exists('Memcached')) {
            $this->useMemcached = true;
            $this->client = new \Memcached();
            if (isset($config['options']) && is_array($config['options'])) {
                foreach ($config['options'] as $k => $v) {
                    $this->client->setOption($k, $v);
                }
            }
            $servers = isset($config['servers']) ? $config['servers'] : array(array('127.0.0.1', 11211, 1));
            $this->client->addServers($servers);
        } elseif (class_exists('Memcache')) {
            $this->client = new \Memcache();
            $servers = isset($config['servers']) ? $config['servers'] : array(array('127.0.0.1', 11211));
            foreach ($servers as $s) {
                $host = isset($s[0]) ? $s[0] : '127.0.0.1';
                $port = isset($s[1]) ? (int)$s[1] : 11211;
                @$this->client->addServer($host, $port);
            }
        } else {
            throw new \RuntimeException('Neither Memcached nor Memcache extension is installed');
        }
    }

    public function get($key, $default = null)
    {
        $full = $this->prefix.$key;
        if ($this->useMemcached) {
            $val = $this->client->get($full);
            if ($val === false && $this->client->getResultCode() !== \Memcached::RES_SUCCESS) {
                return $default;
            }
            return $val;
        } else {
            $val = $this->client->get($full);
            return ($val === false) ? $default : $val;
        }
    }

    public function set($key, $value, $ttl = null)
    {
        $ttl = $this->normalizeTtl($ttl);
        $full = $this->prefix.$key;

        if ($this->useMemcached) {
            return (bool)$this->client->set($full, $value, $ttl ?: 0);
        } else {
            // Memcache: flags 0, expire $ttl
            return (bool)$this->client->set($full, $value, 0, $ttl ?: 0);
        }
    }

    public function delete($key)
    {
        $full = $this->prefix.$key;
        if ($this->useMemcached) {
            return (bool)$this->client->delete($full);
        } else {
            return (bool)$this->client->delete($full);
        }
    }

    public function clear()
    {
        return (bool)$this->client->flush();
    }

    public function has($key)
    {
        // Memcache(d) does not have exists; do a get
        $marker = '__MISS__';
        return $this->get($key, $marker) !== $marker;
    }

    public function increment($key, $by = 1)
    {
        $full = $this->prefix.$key;
        $by = (int)$by;

        if ($this->useMemcached) {
            $val = $this->client->increment($full, $by);
            if ($val === false) {
                // create as $by
                $this->client->add($full, $by);
                return $by;
            }
            return (int)$val;
        } else {
            $val = $this->client->increment($full, $by);
            if ($val === false) {
                $this->client->set($full, $by, 0, 0);
                return $by;
            }
            return (int)$val;
        }
    }

    public function decrement($key, $by = 1)
    {
        $full = $this->prefix.$key;
        $by = (int)$by;

        if ($this->useMemcached) {
            $val = $this->client->decrement($full, $by);
            if ($val === false) {
                $start = 0 - $by;
                $this->client->add($full, $start);
                return $start;
            }
            return (int)$val;
        } else {
            $val = $this->client->decrement($full, $by);
            if ($val === false) {
                $start = 0 - $by;
                $this->client->set($full, $start, 0, 0);
                return $start;
            }
            return (int)$val;
        }
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