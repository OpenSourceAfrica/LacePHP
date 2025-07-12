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

use Lacebox\Shoelace\ApiDocInterface;
use Lacebox\Shoelace\RouterInterface;

class ApiDocGenerator
{
    /** @var array */
    protected $routes;

    /** @var array */
    protected $config;

    /**
     * @param RouterInterface|array $source
     *    Either a RouterInterface (to pull getRoutes()) or a raw routes array
     * @param array|null $config
     *    Optional config overrides; defaults to config()
     */
    public function __construct($source, array $config = null)
    {
        // Load routes
        if ($source instanceof RouterInterface) {
            $this->routes = $source->getRoutes();
        } elseif (is_array($source)) {
            $this->routes = $source;
        } else {
            throw new \InvalidArgumentException(
                "ApiDocGenerator expects RouterInterface or array; "
                . gettype($source) . " given."
            );
        }

        // Load config (for title, version, servers)
        $this->config = $config ?? config();
    }

    /**
     * Build the full OpenAPI document as an associative array.
     */
    public function generate(): array
    {
        // Basic info block
        $openapi = [
            'openapi'    => '3.0.0',
            'info'       => [ /* … */ ],
            'servers'    => $this->buildServers(),
            'paths'      => [],                  // now an array
            'components' => [
                'schemas'         => [],         // now an array
                'securitySchemes' => [],         // now an array
            ],
        ];

        foreach ($this->routes as $route) {
            $method     = strtolower($route['method']);
            $path       = $route['pattern'] ?? $route['uri'] ?? '/';
            $controller = $route['controller'] ?? null;
            $action     = $route['action']     ?? null;

            // Initialize the path/method slot if not set
            if (!isset($openapi['paths'][$path])) {
                $openapi['paths'][$path] = [];
            }

            // Default operation
            $operation = [
                'summary'     => $controller && $action
                    ? "{$controller}@{$action}"
                    : "Handler for {$method} {$path}",
                'tags'        => $controller ? [$this->tagFromController($controller)] : [],
                'responses'   => [
                    '200' => [
                        'description' => 'Successful response',
                    ]
                ],
            ];

            // If controller implements ApiDocInterface, merge its spec
            if ($controller
                && class_exists($controller)
                && in_array(ApiDocInterface::class, class_implements($controller), true)
            ) {
                $spec = $controller::openApiSpec();
                if (isset($spec[$path][$method]) && is_array($spec[$path][$method])) {
                    // Merge operation-level keys
                    $operation = array_replace_recursive($operation, $spec[$path][$method]);
                }
            }

            $openapi['paths'][$path][$method] = $operation;
        }

        return $openapi;
    }

    /**
     * Write out the generated OpenAPI document as prettified JSON.
     */
    public function toJsonFile(string $path): void
    {
        $doc = $this->generate();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            $path,
            json_encode($doc, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Build the servers array from config.
     */
    protected function buildServers(): array
    {
        $base = $this->config['api']['servers']
            ?? $this->config['servers']
            ?? null;

        if (is_array($base)) {
            return $base;
        }

        // Fallback: use base_url from config
        $url = $this->config['base_url']
            ?? (sole_request()->server('HTTP_HOST') ?? null);
        if ($url) {
            return [
                ['url' => rtrim($url, '/')]
            ];
        }

        return [];
    }

    /**
     * Derive a tag name from the controller class (short class name).
     */
    protected function tagFromController(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }
}
