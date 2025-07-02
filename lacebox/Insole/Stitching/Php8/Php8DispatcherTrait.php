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

namespace Lacebox\Insole\Stitching\Php8;

use Lacebox\Shoelace\MiddlewareInterface;
use Lacebox\Sole\UriResolver;

/**
 * Provides dispatch logic optimized for PHP8.
 */
trait Php8DispatcherTrait
{
    /**
     * Dispatch the current HTTP request through registered routes.
     */
    public function dispatch(): mixed
    {
        $method = sole_request()->method();
        $uri    = UriResolver::resolve();
        $route  = $this->resolve($method, $uri);

        if (!$route) {
            http_response_code(404);
            return ['error' => 'Not Found'];
        }

        // Handle middleware
        foreach ($route['middleware'] as $mw) {
            $instance = new $mw();
            if ($instance instanceof MiddlewareInterface) {
                $instance->handle();
            }
        }

        $action = $route['action'];

        if (is_array($action)) {
            [$class, $methodName] = $action;
            if (!class_exists($class)) {
                http_response_code(500);
                return ['error' => "Controller $class not found"];
            }
            try {
                $ref = new \ReflectionMethod($class, $methodName);
                $callable = $ref->isStatic()
                    ? [$class, $methodName]
                    : [new $class(), $methodName];
                return $callable(...$route['params']);
            } catch (\Throwable $e) {
                http_response_code(500);
                return ['error' => $e->getMessage()];
            }
        }

        if (is_callable($action)) {
            try {
                return $action(...$route['params']);
            } catch (\Throwable $e) {
                http_response_code(500);
                return ['error' => $e->getMessage()];
            }
        }

        http_response_code(500);
        return ['error' => 'Invalid handler'];
    }

    /**
     * PSR-style handle() alias for dispatch().
     */
    public function handle(): mixed
    {
        return $this->dispatch();
    }
}