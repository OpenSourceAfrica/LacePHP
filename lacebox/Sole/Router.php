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

namespace Lacebox\Sole;

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Knots\ShoeGateKnots;
use Lacebox\Shoelace\ContainerInterface;
use Lacebox\Shoelace\DispatcherInterface;
use Lacebox\Shoelace\LiningInterface;
use Lacebox\Shoelace\MiddlewareInterface;
use Lacebox\Shoelace\RouterInterface;
use Lacebox\Shoelace\ShoeResponderInterface;
use Lacebox\Sole\Http\ShoeResponder;

/**
 * Central application router integrating versioned linings.
 */
class Router implements RouterInterface, DispatcherInterface, ContainerInterface
{
    use SingletonTrait;

    /** @var LiningInterface */
    protected $lining;
    /** @var ShoeResponderInterface */
    protected $responder;
    /** @var callable|null */
    protected $guardResolver;
    /** @var array */
    protected $bindings = [];
    /** @var array */
    protected $config = [];

    /** @var string[] fully-qualified middleware class names to run on every request */
    protected $globalMiddleware = [];

    /** @var array stores the active group stack */
    protected $groupStack = [];

    public function __construct(LiningInterface $lining, ?ShoeResponderInterface $responder = null)
    {
        $this->lining = $lining;
        $this->responder = $responder ?? ShoeResponder::getInstance();
    }

    public function load(LiningInterface $lining, ?ShoeResponderInterface $responder = null)
    {
        $this->lining = $lining;
        $this->responder = $responder ?? ShoeResponder::getInstance();
    }

    public function setGuardResolver(callable $resolver): void
    {
        $this->guardResolver = $resolver;
    }

    public function get($patternOrId, $actionOrNull = null)
    {
        if (func_num_args() === 2) {
            $this->addRoute('GET', $patternOrId, $actionOrNull);
            return;
        }
        return $this->make($patternOrId);
    }

    public function post(string $pattern, $action, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $action, $middleware);
    }

    public function put(string $pattern, $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $pattern, $action, $middleware);
    }

    public function patch(string $pattern, $action, array $middleware = []): void
    {
        $this->addRoute('PATCH', $pattern, $action, $middleware);
    }

    public function delete(string $pattern, $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $pattern, $action, $middleware);
    }

    public function options(string $pattern, $action, array $middleware = []): void
    {
        $this->addRoute('OPTIONS', $pattern, $action, $middleware);
    }

    /**
     * Magic handler for any sewGet, sewPost, sewPut, sewPatch, sewDelete, etc.
     *
     * @param  string  $name    e.g. "sewGet" or "sewPost"
     * @param  array   $args    [$pattern, $action, $middleware?]
     */
    public function __call($name, $args)
    {
        // Look for sew + Verb
        if (preg_match('/^sew([A-Za-z]+)$/', $name, $m)) {
            // e.g. "Get" → "GET", "Post" → "POST"
            $verb = strtoupper($m[1]);

            // Extract arguments (pattern, action, middleware)
            $pattern    = $args[0] ?? '/';
            $action     = $args[1] ?? null;
            $middleware = $args[2] ?? [];

            // Delegate to your existing addRoute
            $this->addRoute($verb, $pattern, $action, $middleware);
            return;   // explicit “void” return
        }

        throw new \BadMethodCallException("Method {$name} does not exist on Router");
    }
    public function getRoutes(): array
    {
        $routes = [];
        foreach ($this->lining->getRoutes() as $method => $defs) {
            foreach ($defs as $r) {
                $routes[] = [
                    'method' => $method,
                    'pattern' => $r['pattern'],
                    'action' => $r['action'],
                    'middleware' => $r['middleware'] ?? [],
                ];
            }
        }
        return $routes;
    }

    public function resolve(string $method, string $uri): ?array
    {
        return $this->lining->resolve($method, $uri);
    }

    /**
     * Set a list of middleware classes that run on every request,
     * before any route-specific middleware or handler.
     *
     * @param string[] $middleware Fully-qualified class names.
     */
    public function setGlobalMiddleware(array $middleware): void
    {
        $this->globalMiddleware = $middleware;
    }

    public function dispatch()
    {
        $method = sole_request()->method();
        $uri    = UriResolver::resolve();
        $route  = $this->resolve($method, $uri);

        if (! $route) {
            log_error('404', "No route found for {$method} {$uri}");
            return $this->responder->notFound('Not Found');
        }

        if (in_array(ShoeGateKnots::class, $this->globalMiddleware, true)) {
            $gate = new ShoeGateKnots();
            $gate->handle();
        }

        // 0a) Run only the GateKnots first
        foreach ($this->globalMiddleware as $mwClass) {
            if ($mwClass === ShoeGateKnots::class) {
                $instance = new $mwClass();
                if ($instance instanceof MiddlewareInterface) {
                    $instance->handle();
                }
                break; // only one gate
            }
        }

        // 1) Run all global middleware first:
        foreach ($this->globalMiddleware as $mwClass) {
            if (! class_exists($mwClass)) {
                continue;
            }
            $instance = new $mwClass();
            if ($instance instanceof MiddlewareInterface) {
                $instance->handle();
            }
        }

        // Extract middleware (with special _guard)
        $middleware = $route['middleware'] ?? [];

        // Handle guard
        if (isset($middleware['_guard']) && is_callable($this->guardResolver)) {
            $guardName = $middleware['_guard'];
            unset($middleware['_guard']);
            $guard = ($this->guardResolver)($guardName);
            if (! $guard || ! $guard->check()) {
                log_error('401', "Guard “{$guardName}” failed for {$method} {$uri}");
                return $this->responder->unauthorized('Unauthorized');
            }
        }

        // Run real middleware (knots)
        foreach ($middleware as $entry) {

            // Case A: [ClassName, [arg1, arg2, …]]
            if (is_array($entry) && isset($entry[0], $entry[1]) && is_array($entry[1])) {
                list($class, $args) = $entry;
                if (! class_exists($class)) {
                    continue;
                }

                $instance = new $class(...$args);

                // Case B: simple ClassName string
            } elseif (is_string($entry) && class_exists($entry)) {
                $instance = new $entry();

            } else {
                // unrecognized entry: skip
                continue;
            }

            // Finally, handle it if it implements the interface
            if ($instance instanceof \Lacebox\Shoelace\MiddlewareInterface) {
                $instance->handle();
            }
        }

        // *** USE 'action' *** not 'handler'
        $handler = $route['action'];
        $params  = $route['params'] ?? [];

        try {
            if (is_array($handler)) {
                [$class, $methodName] = $handler;
                if (! class_exists($class)) {
                    return $this->responder->serverError("Controller {$class} not found");
                }
                $ref    = new \ReflectionMethod($class, $methodName);
                $result = $ref->isStatic()
                    ? $class::$methodName()
                    : (new $class())->$methodName();

            } elseif (is_callable($handler)) {
                $result = $handler(...$params);

            } else {
                return $this->responder->serverError('Invalid handler');
            }
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            log_error('500', "Exception on {$method} {$uri}: {$msg}");
            return $this->responder->serverError($msg);
        }

        // Format response
        if (is_array($result) || is_object($result)) {
            return $this->responder->json($result);
        }
        return (string)$result;
    }

    public function bind(string $id, callable $concrete): void
    {
        $this->bindings[$id] = $concrete;
    }

    public function make(string $id)
    {
        if (! class_exists($id)) {
            throw new \InvalidArgumentException(
                "Cannot resolve service or class “{$id}”.\n\n"
                . "Did you mean to register a route? If so, call "
                . "\$router->sewGet(...) or another HTTP helper instead of get()."
            );
        }

        return isset($this->bindings[$id])
            ? call_user_func($this->bindings[$id])
            : new $id();
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }


    /**
     * Start a group of routes.
     *
     * @param array    $attrs  ['prefix'=>'/admin', 'middleware'=>[...], 'namespace'=>'...']
     * @param callable $cb     function(Router $router){ $router->get(...); }
     */
    public function group(array $attrs, callable $cb): void
    {
        // push the new group onto the stack
        $this->groupStack[] = array_merge(
            ['prefix'=>'', 'middleware'=>[], 'namespace'=>''],
            $attrs
        );

        // run the user’s routes inside this group
        $cb($this);

        // pop the group off the stack
        array_pop($this->groupStack);
    }

    /**
     * Shoe-themed alias of addRoute()
     */
    public function sewRoute(string $method, string $pattern, $action, array $middleware = []): void
    {
        $this->addRoute($method, $pattern, $action, $middleware);
    }

    /**
     * Add a route, applying any active group defaults first.
     */
    public function addRoute(string $method, string $pattern, $action, array $middleware = []): void
    {
        // if there’s an active group, merge its attributes
        if (! empty($this->groupStack)) {
            // get the *top* (most recent) group
            $group = end($this->groupStack);

            // 1) prefix the URI
            $pattern = rtrim($group['prefix'], '/') . '/' . ltrim($pattern, '/');

            // 2) namespace the controller (if action is [ControllerClass, method])
            if (is_array($action) && is_string($action[0]) && $group['namespace']) {
                $action[0] = rtrim($group['namespace'], '\\') . '\\' . ltrim($action[0], '\\');
            }

            // 3) merge middleware arrays
            $middleware = array_merge($group['middleware'], $middleware);
        }

        // now hand off to whatever your original addRoute logic was
        $this->lining->addRoute($method, $pattern, $action, $middleware);
    }
}