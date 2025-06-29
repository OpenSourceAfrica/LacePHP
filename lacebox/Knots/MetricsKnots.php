<?php
namespace Lacebox\Knots;

use Lacebox\Shoelace\MiddlewareInterface;
use Lacebox\Sole\MetricsCollector;
use Lacebox\Sole\UriResolver;

class MetricsKnots implements MiddlewareInterface
{
    public function handle(): void
    {
        // Load merged config
        $config = config();

        // 2) Skip if metrics are disabled in config
        if (empty($config['metrics']['enabled'] ?? true)) {
            return;
        }

        // Resolve current path
        $uri = UriResolver::resolve();

        // 1) Skip the metrics‐endpoint itself
        $metricsPath = '/' . trim($config['endpoints']['metrics'] ?? 'lace/metrics', '/');
        if ($uri === $metricsPath) {
            return;
        }

        // start timer
        $start = microtime(true);

        // register shutdown handler to record on exit
        register_shutdown_function(function() use ($start) {
            $ms   = (microtime(true) - $start) * 1000;
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri    = UriResolver::resolve();
            $key = strtolower($method).' '.$uri;

            // record one request
            MetricsCollector::increment('requests_total');
            // record timing
            MetricsCollector::recordTiming('request_duration_ms{route="'.addslashes($key).'"}', $ms);
        });
    }
}