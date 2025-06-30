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
     * Resolve an incoming HTTP method and URI to a matching route.
     *
     * @param string $method
     * @param string $uri
     * @return array|null
     */
    public function resolve(string $method, string $uri): ?array
    {
        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            // Convert {param} to named capturing groups
            $regex = preg_replace('#\{([^}]+)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
                return [
                    'action' => $route['action'],
                    'middleware' => $route['middleware'],
                    'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
                ];
            }
        }

        return null;
    }
}
