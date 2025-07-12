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

/**
 * LiningCoreTrait.php
 * Contains routing storage and resolution logic for PHP versioned linings.
 */

namespace Lacebox\Insole\Stitching;

trait LiningCoreTrait
{
    /** @var array */
    protected $routes = [];

    /**
     * Add a route pattern to the routing table.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $pattern Route URI pattern
     * @param callable|array $action Controller or closure
     * @param array $middleware List of middleware classes
     */
    public function addRoute(string $method, string $pattern, $action, array $middleware = [])
    {
        $this->routes[strtoupper($method)][] = [
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    /**
     * Get all configured routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param  string  $method  HTTP verb, e.g. "GET"
     * @param  string  $uri     Request URI, e.g. "/hello" or "/hello/John"
     * @return array|null       [
     *   'action'     => array|Closure,
     *   'middleware' => array,
     *   'params'     => array<string,mixed>
     * ] or null if no match
     */
    public function resolve(string $method, string $uri): ?array
    {
        // 0) normalize to leading slash, no trailing slash (except root)
        $uri = '/' . trim($uri, '/');

        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            $pattern = $route['pattern'];

            //
            // 1) Optional parameters: {param?} → (?P<param>[^/]+)?
            //
            $pattern = preg_replace_callback(
                '#\{(\w+)\?}#',
                function($m) {
                    return '(?P<' . $m[1] . '>[^/]+)?';
                },
                $pattern
            );

            //
            // 2) Required parameters: {param} → (?P<param>[^/]+)
            //
            $pattern = preg_replace(
                '#\{(\w+)}#',
                '(?P<$1>[^/]+)',
                $pattern
            );

            //
            // 3) Anchor and build final regex
            //
            $regex = '#^' . $pattern . '$#';

            if (preg_match($regex, $uri, $matches)) {
                return [
                    'action'     => $route['action'],               // can be [ControllerClass,method] or a Closure
                    'middleware' => $route['middleware'] ?? [],     // any per-route middleware
                    'params'     => array_filter(
                        $matches,
                        'is_string',
                        ARRAY_FILTER_USE_KEY
                    )
                ];
            }
        }

        return null;
    }
}
