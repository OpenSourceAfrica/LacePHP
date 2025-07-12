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

namespace Lacebox\Insole\Stitching\Php7;

use Lacebox\Shoelace\MiddlewareInterface;
use Lacebox\Sole\UriResolver;

/**
 * Provides dispatch logic for PHP7 lining.
 */
trait Php7DispatcherTrait
{
    /**
     * Dispatch the current HTTP request through registered routes.
     */
    public function dispatch()
    {
        $method = sole_request()->method();
        $uri    = UriResolver::resolve();
        $route  = $this->resolve($method, $uri);

        if (!$route) {
            http_response_code(404);
            echo json_encode(['error' => 'Not Found']);
            return;
        }

        foreach ($route['middleware'] as $mw) {
            $m = new $mw();
            if ($m instanceof MiddlewareInterface) {
                $m->handle();
            }
        }

        $action = $route['action'];
        if (is_array($action)) {
            list($class, $method) = $action;
            $instance = new $class();
            return call_user_func_array([$instance, $method], $route['params']);
        } elseif (is_callable($action)) {
            return call_user_func_array($action, $route['params']);
        }

        http_response_code(500);
        echo json_encode(['error' => 'Invalid handler']);
    }

    /**
     * PSR-style handle() alias for dispatch.
     */
    public function handle()
    {
        return $this->dispatch();
    }
}