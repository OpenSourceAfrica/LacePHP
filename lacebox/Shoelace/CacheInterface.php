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

namespace Lacebox\Shoelace;

interface CacheInterface
{
    /**
     * Get a value from cache.
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Put a value into cache.
     * @param string    $key
     * @param mixed     $value
     * @param int|null  $ttl  Seconds-to-live; null = driver default; 0 = forever (if supported)
     * @return bool
     */
    public function set($key, $value, $ttl = null);

    /** @return bool */
    public function delete($key);

    /** @return bool */
    public function clear();

    /** @return bool */
    public function has($key);

    /**
     * Atomically increment a numeric cache value (creates if missing).
     * @param string $key
     * @param int    $by
     * @return int The new value
     */
    public function increment($key, $by = 1);

    /**
     * Atomically decrement a numeric cache value (creates if missing).
     * @param string $key
     * @param int    $by
     * @return int The new value
     */
    public function decrement($key, $by = 1);

    /**
     * Get from cache or compute & store.
     * @param string   $key
     * @param int|null $ttl
     * @param callable $callback
     * @return mixed
     */
    public function remember($key, $ttl, $callback);
}