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

namespace Lacebox\Heel;

use Lacebox\Sole\Cobble\ConnectionManager;
use Lacebox\Sole\ShoeResponder;

class Dashboard
{
    /**
     * Render the dashboard page with inline HTML.
     */
    public function show(): string
    {
        // Gather server-side stats
        $uptime   = $this->getUptime();
        $mem      = memory_get_usage(true);
        $memPeak  = memory_get_peak_usage(true);
        $dbOk     = $this->checkDb() ? 'yes' : 'no';
        $php      = PHP_VERSION;

        // Determine protocol and host
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $origin = "{$scheme}://{$host}";

        // SCRIPT_NAME is the front‐controller path, e.g. '/public/index.php'
        $script = $_SERVER['SCRIPT_NAME'] ?? '';

        // REQUEST_URI is the full incoming path, e.g. '/public/index.php/lace/dashboard'
        $uri    = $_SERVER['REQUEST_URI'] ?? '';

        // Should we prepend SCRIPT_NAME?
        $needsScript = $script && strpos($uri, $script) === 0;

        // Your configured metrics route (no leading slash)
        $route = trim(config()['endpoints']['metrics'] ?? 'lace/metrics', '/');

        // Build the full URL
        $metricsEndpoint = $origin
            . ($needsScript ? $script . '/' : '/')
            . $route;


        // Build inline HTML
        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>🎽 LacePHP Dashboard</title>
  <style>
    body { font-family: sans-serif; margin: 2rem; }
    h1   { margin-bottom: 0.5rem; }
    table { border-collapse: collapse; width: 50%; margin-bottom: 2rem; }
    th,td { border: 1px solid #ccc; padding: 0.5rem 1rem; text-align: left; }
    pre { background: #f4f4f4; padding: 1rem; }
  </style>
</head>
<body>
  <h1>🎽 LacePHP Dashboard</h1>

  <h2>Server Info</h2>
  <table>
    <tr><th>PHP Version</th><td>{$php}</td></tr>
    <tr><th>Uptime (s)</th><td>{$uptime}</td></tr>
    <tr><th>Memory Used (bytes)</th><td>{$mem}</td></tr>
    <tr><th>Memory Peak (bytes)</th><td>{$memPeak}</td></tr>
    <tr><th>DB Connected</th><td>{$dbOk}</td></tr>
  </table>

  <h2>Metrics</h2>
  <pre id="metrics">Loading metrics…</pre>

    <script>
      fetch('{$metricsEndpoint}')
        .then(r => r.text())
        .then(txt => {
          document.getElementById('metrics').textContent = txt;
        })
        .catch(err => {
          document.getElementById('metrics').textContent =
            'Error loading metrics: ' + err;
        });
    </script>

</body>
</html>
HTML;

        return (ShoeResponder::getInstance())->html($html);
    }

    protected function getUptime(): float
    {
        if (is_readable('/proc/uptime')) {
            $parts = explode(' ', trim(file_get_contents('/proc/uptime')));
            return round((float)$parts[0], 2);
        }
        return 0.0;
    }

    protected function checkDb(): bool
    {
        try {
            $pdo = ConnectionManager::getConnection();
            $pdo->query('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}