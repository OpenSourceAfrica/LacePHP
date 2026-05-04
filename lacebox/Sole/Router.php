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

    /** @var array<string, array> */
    protected $middlewareGroups = [];

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

    /**
     * Register a named middleware group.
     * e.g. $router->middlewareGroup('auth', [AuthMiddleware::class]);
     */
    public function middlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
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
            logger('404', "No route found for {$method} {$uri}");
            return $this->responder->notFound('Not Found');
        }

        // 1) Run all global middleware first:
        foreach ($this->globalMiddleware as $mwClass) {
            if (! class_exists($mwClass)) {
                continue;
            }
            $instance = new $mwClass();
            if ($instance instanceof MiddlewareInterface) {
                $out = $instance->handle();
                if ($out !== null) {
                    return $out;
                }
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
                logger('401', "Guard “{$guardName}” failed for {$method} {$uri}");
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
                $out = $instance->handle();
                if ($out !== null) {
                    return $out; // STOP request pipeline
                }
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

                $ref      = new \ReflectionMethod($class, $methodName);
                $instance = $ref->isStatic() ? null : new $class();

                // build argument list by matching method's param names to your $params
                $args = [];
                foreach ($ref->getParameters() as $param) {
                    $name = $param->getName();
                    if (array_key_exists($name, $params)) {
                        $args[] = $params[$name];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        // you could throw here instead if missing required param
                        $args[] = null;
                    }
                }

                // invoke with arguments
                $result = $ref->invokeArgs($instance, $args);

            } elseif (is_callable($handler)) {
                $result = $handler(...array_values($params));

            } else {
                return $this->responder->serverError('Invalid handler');
            }
        } catch (\Throwable $e) {

            $debug = false;
            if (function_exists('config')) {
                $cfg = config();
                $debug = !empty($cfg['boot']['debug'] ?? false);
            }

            // Always log full details (even in production)
            logger('500', "Exception on {$method} {$uri}: " . $e->getMessage()
                . " in " . $e->getFile() . ":" . $e->getLine()
            );

            if ($debug) {
                // return rich JSON when debugging
                http_response_code(500);
                header('Content-Type: application/json');

                return json_encode([
                    'error'     => $e->getMessage(),
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'  => $e->getTraceAsString(),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            // production: don’t leak file paths
            return $this->responder->serverError($e->getMessage());
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
        $defaults = ['prefix' => '', 'middleware' => [], 'namespace' => ''];
        $attrs = array_merge($defaults, $attrs);

        // Parent group (already merged from previous nesting)
        $parent = !empty($this->groupStack)
            ? end($this->groupStack)
            : $defaults;

        // Merge prefix (parent + child)
        $prefix = $parent['prefix'];
        if (!empty($attrs['prefix'])) {
            $prefix = $this->joinUri($parent['prefix'], $attrs['prefix']);
        }

        // Merge namespace (parent\child)
        $namespace = $parent['namespace'];
        if (!empty($attrs['namespace'])) {
            if (!empty($namespace)) {
                $namespace = rtrim($namespace, '\\') . '\\' . ltrim($attrs['namespace'], '\\');
            } else {
                $namespace = ltrim($attrs['namespace'], '\\');
            }
        }

        // Merge middleware (parent + child)
        $middleware = array_merge(
            $this->expandMiddlewareList($parent['middleware'] ?? []),
            $this->expandMiddlewareList($attrs['middleware'] ?? [])
        );

        // Push merged group
        $this->groupStack[] = [
            'prefix' => $prefix,
            'middleware' => $middleware,
            'namespace' => $namespace,
        ];

        // Run group routes
        $cb($this);

        // Pop
        array_pop($this->groupStack);
    }

    /**
     * Join two URI segments safely.
     * e.g. joinUri('/v1', '/login') => '/v1/login'
     */
    private function joinUri(string $a, string $b): string
    {
        $a = '/' . trim($a, '/');
        $b = '/' . trim($b, '/');

        // If a is just "/", treat as empty
        if ($a === '/') $a = '';

        $out = rtrim($a, '/') . '/' . ltrim($b, '/');
        $out = '/' . trim($out, '/');

        return $out === '//' ? '/' : $out;
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
        // Normalise base pattern
        $pattern = '/' . ltrim($pattern, '/');

        // Apply the merged group (if any)
        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);

            // 1) prefix
            if (!empty($group['prefix'])) {
                $pattern = $this->joinUri($group['prefix'], $pattern);
            }

            // 2) namespace controller if action is [Class, method]
            if (is_array($action) && isset($action[0], $action[1]) && is_string($action[0]) && !empty($group['namespace'])) {
                // If already fully qualified (\Weave\...), don’t prefix
                $class = $action[0];

                // Absolute: "\Vendor\App\Controller"  => do nothing
                if (strlen($class) > 0 && $class[0] === '\\') {
                    $action[0] = ltrim($class, '\\');
                } else {
                    $ns = trim($group['namespace'], '\\');
                    $cls = trim($class, '\\');

                    // If class already begins with the namespace, keep it
                    // Otherwise treat as relative and prefix it
                    $startsWithNs = (strpos($cls . '\\', $ns . '\\') === 0);

                    $action[0] = $startsWithNs ? $cls : ($ns . '\\' . $cls);
                }
            }

            // 3) middleware merge
            $middleware = array_merge(
                (array)($group['middleware'] ?? []),
                $this->expandMiddlewareList($middleware)
            );
        }

        // AUTO-CREATE PREFLIGHT (route-level, not global)
        // If this route uses CorsKnots, ensure OPTIONS exists for the same path.
        if (strtoupper($method) !== 'OPTIONS') {
            $mwList = is_array($middleware) ? $middleware : [];

            if (in_array(\Lacebox\Knots\CorsKnots::class, $mwList, true)) {
                // Add OPTIONS directly to lining to avoid recursion into addRoute()
                $this->lining->addRoute('OPTIONS', $pattern, function () {
                    // CorsKnots will set headers; we only need a successful empty response.
                    http_response_code(204);
                    return '';
                }, $mwList);
            }
        }

        $this->lining->addRoute($method, $pattern, $action, $middleware);
    }

    /**
     * Expand middleware entries.
     * - "auth" => expands to middleware group classes
     * - ClassName => stays
     * - [ClassName, [args]] => stays
     */
    protected function expandMiddlewareList($middleware): array
    {
        if ($middleware === null) return [];

        // single string: could be a group OR class
        if (is_string($middleware)) {
            if (isset($this->middlewareGroups[$middleware])) {
                return $this->middlewareGroups[$middleware];
            }
            return [$middleware];
        }

        if (!is_array($middleware)) return [];

        $out = [];
        foreach ($middleware as $mw) {
            if (is_string($mw) && isset($this->middlewareGroups[$mw])) {
                foreach ($this->middlewareGroups[$mw] as $g) {
                    $out[] = $g;
                }
            } else {
                $out[] = $mw;
            }
        }

        return $out;
    }

}