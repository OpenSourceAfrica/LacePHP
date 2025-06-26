<?php

namespace Lacebox\Knots;

use Lacebox\Shoelace\MiddlewareInterface;
use Lacebox\Sole\DebugCollector;
use Lacebox\Sole\UriResolver;

/**
 * MagicDebugKnots logs each step of the request lifecycle when activated.
 * Activated via ?debug=lace in the query string.
 */
class MagicDebugKnots implements MiddlewareInterface
{
    public function handle(): void
    {
        if (isset($_GET['debug']) && $_GET['debug'] === 'lace') {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri    = UriResolver::resolve();
            DebugCollector::add("[START] {$method} {$uri}");

            register_shutdown_function(function() use ($method, $uri) {
                $mem = memory_get_usage(true);
                DebugCollector::add("[END] {$method} {$uri} - memory_usage={$mem}");
            });
        }
    }
}