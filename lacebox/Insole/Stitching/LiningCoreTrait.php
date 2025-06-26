<?php
//
//namespace Lacebox\Insole\Stitching;
//
//use Lacebox\Sole\UriResolver;
//use Lacebox\Shoelace\MiddlewareInterface;
//
//trait LiningCoreTrait
//{
//    protected $routes = [];
//    protected $bindings = [];
//    protected $middlewareGroups = [];
//
//    public function addRoute($method, $pattern, $action, $middleware = [])
//    {
//        if (is_string($middleware) && isset($this->middlewareGroups[$middleware])) {
//            $middleware = $this->middlewareGroups[$middleware];
//        }
//
//        $this->routes[strtoupper($method)][] = [
//            'pattern' => $pattern,
//            'action' => $action,
//            'middleware' => $middleware,
//        ];
//    }
//
//    public function getRoutes(): array
//    {
//        return $this->routes;
//    }
//
//    public function resolve(string $method, string $uri)
//    {
//        $routes = $this->routes[$method] ?? [];
//        foreach ($routes as $route) {
//            $regex = preg_replace('#\{([^}]+)\}#', '(?P<$1>[^/]+)', $route['pattern']);
//            if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
//                return [
//                    'action' => $route['action'],
//                    'middleware' => $route['middleware'],
//                    'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)
//                ];
//            }
//        }
//        return null;
//    }
//
//    public function dispatch()
//    {
//        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
//        $uri = (new UriResolver())->resolve();
//        $route = $this->resolve($method, $uri);
//
//        if (!$route) {
//            http_response_code(404);
//            echo json_encode(['error' => 'Route not found']);
//            return;
//        }
//
//        foreach ($route['middleware'] as $middlewareClass) {
//            $middleware = new $middlewareClass();
//            if ($middleware instanceof MiddlewareInterface) {
//                $middleware->handle();
//            }
//        }
//
//        if (is_array($route['action'])) {
//            call_user_func_array([new $route['action'][0], $route['action'][1]], $route['params']);
//        } elseif (is_callable($route['action'])) {
//            call_user_func_array($route['action'], $route['params']);
//        }
//    }
//
//    public function bind(string $id, callable $concrete)
//    {
//        $this->bindings[$id] = $concrete;
//    }
//
//    public function make(string $class)
//    {
//        return isset($this->bindings[$class])
//            ? call_user_func($this->bindings[$class])
//            : new $class();
//    }
//
//    public function get(string $id)
//    {
//        return $this->make($id);
//    }
//
//    public function handle()
//    {
//        $this->dispatch();
//    }
//}


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
