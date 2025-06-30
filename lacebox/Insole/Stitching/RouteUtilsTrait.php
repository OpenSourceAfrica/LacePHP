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

namespace Lacebox\Insole\Stitching;

trait RouteUtilsTrait
{
    protected $routes = [];

    protected function doResolve(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if (
                strtoupper($method) === $route['method'] &&
                preg_match($this->buildPatternRegex($route['uri']), $uri, $matches)
            ) {
                return [
                    'handler' => $route['handler'],
                    'guard' => $route['guard'] ?? null,
                    'middleware' => $route['middleware'] ?? [],
                    'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)
                ];
            }
        }

        return null;
    }

    private function buildPatternRegex(string $pattern): string
    {
        $regex = preg_replace('#\{([^}]+)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}