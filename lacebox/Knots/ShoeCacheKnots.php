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

namespace Lacebox\Knots;

use Lacebox\Shoelace\MiddlewareInterface;
use Lacebox\Sole\UriResolver;

class ShoeCacheKnots implements MiddlewareInterface
{
    protected $cacheDir;
    protected $ttl;
    protected $exemptPaths = [];

    public function __construct()
    {
        $cfg = config();

        // Only enable if cache.enabled is true
        if (empty($cfg['cache']['enabled'] ?? false)) {
            // TTL zero → effectively disabled
            $this->ttl = 0;
        } else {
            $this->ttl = (int)($cfg['cache']['ttl_seconds'] ?? 60);
        }

        // Build cache directory
        $this->cacheDir = dirname(__DIR__, 2)
            . '/'
            . ltrim($cfg['cache']['path'] ?? 'shoebox/cache/responses', '/');
        if (! is_dir($this->cacheDir)) {
            if (!is_dir($this->cacheDir)) {
                @mkdir($this->cacheDir, 0755, true);
            }
        }

        // Gather all built-in endpoints to exempt
        $eps = $cfg['endpoints'] ?? [];
        foreach (['docs','health','dashboard','metrics'] as $key) {
            if (isset($eps[$key])) {
                $this->exemptPaths[] = '/' . trim($eps[$key], '/');
            }
        }
    }

    public function handle(): void
    {
        // If caching disabled or not GET → do nothing
        if ($this->ttl <= 0 || (sole_request()->method()) !== 'GET') {
            return;
        }

        $uri = UriResolver::resolve();

        // If this URI is one of the exempt ones, skip caching
        foreach ($this->exemptPaths as $ex) {
            if ($uri === $ex) {
                return;
            }
        }

        // Otherwise proceed with cache logic
        $key  = md5($uri);
        $file = "{$this->cacheDir}/{$key}.cache";

        // Serve fresh cache if within TTL
        if (file_exists($file) && (time() - filemtime($file)) < $this->ttl) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            readfile($file);
            exit;
        }

        // Capture & store on shutdown
        ob_start();
        register_shutdown_function(function() use ($file) {
            $content = ob_get_clean();
            if (http_response_code() === 200) {
                file_put_contents($file, $content);
            }
            echo $content;
        });
    }
}