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

namespace Lacebox\Sole\Grip\Session;

use Lacebox\Sole\Grip\CacheManager;

class CacheSessionHandler
{
    /** @var string */
    protected $prefix = 'session:';

    /** @var int */
    protected $ttl;

    public function __construct(array $config = array())
    {
        if (isset($config['prefix'])) {
            $this->prefix = (string)$config['prefix'];
        }

        $this->ttl = isset($config['ttl'])
            ? (int)$config['ttl']
            : (int) ini_get('session.gc_maxlifetime');
    }

    protected function key($session_id)
    {
        return $this->prefix . $session_id;
    }

    public function open($savePath, $sessionName)
    {
        // Nothing needed; cache is managed by CacheManager.
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($session_id)
    {
        try {
            $cache = CacheManager::getInstance()->driver();
            $data  = $cache->get($this->key($session_id), '');
            return (string)$data;
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function write($session_id, $session_data)
    {
        try {
            $cache = CacheManager::getInstance()->driver();
            // Assuming your CacheInterface has set($key, $value, $ttl = null)
            $cache->set($this->key($session_id), $session_data, $this->ttl);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function destroy($session_id)
    {
        try {
            $cache = CacheManager::getInstance()->driver();
            if (method_exists($cache, 'delete')) {
                $cache->delete($this->key($session_id));
            } elseif (method_exists($cache, 'forget')) {
                $cache->forget($this->key($session_id));
            } else {
                $cache->set($this->key($session_id), null, 1);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return true;
    }

    public function gc($max_lifetime)
    {
        // TTL-based, nothing to do.
        return true;
    }
}