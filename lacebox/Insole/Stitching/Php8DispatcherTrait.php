<?php

namespace Lacebox\Insole\Stitching;

use Lacebox\Sole\UriResolver;
use Lacebox\Shoelace\MiddlewareInterface;

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
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
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