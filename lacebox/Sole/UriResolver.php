<?php
namespace Lacebox\Sole;

class UriResolver
{
    public static function resolve(): string
    {
        if (PHP_SAPI === 'cli') {
            global $argv;

            // If a URI is passed in CLI, use it: e.g. php lace app:run /api/hello
            return $argv[2] ?? '/';
        }

        $uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

        // Remove /index.php if present
        $uri = preg_replace('#/index\.php#', '', $uri);

        // Remove script directory (e.g. /public, or /lacephp/public)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $uri = preg_replace('#^' . preg_quote($scriptDir) . '#', '', $uri);

        return rtrim($uri, '/') ?: '/';
    }
}