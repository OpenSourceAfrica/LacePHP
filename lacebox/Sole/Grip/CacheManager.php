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

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Shoelace\CacheInterface;

class CacheManager
{
    use SingletonTrait;

    /** @var CacheInterface */
    protected $driver;

    /**
     * @param array $config  e.g. ['driver'=>'file','path'=>..., 'redis'=>[...], 'memcache'=>[...]]
     */
    private function __construct()
    {
        $config = config('cache');

        $driver = isset($config['driver']) ? $config['driver'] : 'file';

        if ($driver === 'redis') {
            $this->driver = new RedisCache(isset($config['redis']) ? $config['redis'] : array());
        } elseif ($driver === 'memcache' || $driver === 'memcached') {
            $this->driver = new MemcacheCache(isset($config['memcache']) ? $config['memcache'] : array());
        } else { // file
            $this->driver = new FileCache(isset($config['path']) ? $config['path'] : null, isset($config['default_ttl']) ? $config['default_ttl'] : null);
        }
    }

    /** @return CacheInterface */
    public function driver()
    {
        return $this->driver;
    }
}