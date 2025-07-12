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

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;
use Lacebox\Sole\ApiDocGenerator;
use Lacebox\Shoelace\RouterInterface;

class RouteCommand implements CommandInterface
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function name(): string
    {
        return 'route';
    }

    public function description(): string
    {
        return 'Manage all routes (create|run). Usage: php lace route (list | docs)';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1]) && $argv[1] === $this->name();
    }

    public function run(array $argv): void
    {
        $sub = $argv[2] ?? null;
        switch ($sub) {
            case 'list':
                $routes = $this->router->getRoutes();
                echo "\n Registered Routes:\n";
                foreach ($routes as $r) {
                    $method     = $r['method']     ?? 'GET';
                    $uri        = $r['uri']        ?? '/';
                    $controller = $r['controller'] ?? 'Closure';
                    $action     = $r['action']     ?? 'invoke';
                    if (is_array($controller)) {
                        $controller = implode('\\', $controller);
                    }
                    if (is_array($action)) {
                        $action = implode('::', $action);
                    }
                    echo sprintf("[%s] %s → %s@%s\n",
                        $method, $uri, $controller, $action
                    );
                }
                break;

            case 'docs':
                $generator = new ApiDocGenerator($this->router);
                $outputDir  = __DIR__ . '/../../../shoebox/outsole/docs';
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }
                $outputPath = $outputDir . '/openapi.json';
                $generator->toJsonFile($outputPath);
                echo "\n OpenAPI docs generated at: public/outsole/docs/openapi.json\n";
                break;

            default:
                echo "\n Usage: php lace route [list|docs]\n";
        }
    }
}