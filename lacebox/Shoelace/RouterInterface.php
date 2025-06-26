<?php

namespace Lacebox\Shoelace;

interface RouterInterface
{
    /**
     * Add a route to the router.
     *
     * @param string $method     HTTP method (GET, POST, etc.)
     * @param string $pattern    URI pattern (e.g. /users/{id})
     * @param callable|array $action The handler (controller or closure)
     * @param array $middleware  Array of middleware class names
     */
    public function addRoute(string $method, string $pattern, $action, array $middleware = []);

    public function getRoutes(): ?array;

    /**
     * Resolve a route based on method and URI.
     *
     * @param string $method
     * @param string $uri
     * @return array|null
     */
    public function resolve(string $method, string $uri);
}