<?php

namespace Lacebox\Knots;

use Lacebox\Shoelace\MiddlewareInterface;

class CorsKnots implements MiddlewareInterface
{
    public function handle(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }
}
