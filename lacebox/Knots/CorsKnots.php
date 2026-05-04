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

namespace Lacebox\Knots;

use Lacebox\Shoelace\MiddlewareInterface;

class CorsKnots implements MiddlewareInterface
{
    public function handle()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // If request comes from file://, browsers send Origin: null
        // If curl hits it, Origin is often not sent at all
        if ($origin === '' || $origin === null) {
            // No Origin header → not a CORS request; you can still set defaults safely
            $origin = '*';
        }

        $allowCredentials = true;

        // If using credentials, you MUST NOT use "*"
        if ($allowCredentials) {
            // Reflect actual origin (including "null")
            header("Access-Control-Allow-Origin: {$origin}");
            header("Access-Control-Allow-Credentials: true");
            header("Vary: Origin");
        } else {
            header("Access-Control-Allow-Origin: *");
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

        // Reflect requested headers
        $reqHeaders = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '';
        if ($reqHeaders) {
            header("Access-Control-Allow-Headers: {$reqHeaders}");
        } else {
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Lace-Session, X-CSRF-TOKEN");
        }

        header("Access-Control-Max-Age: 86400");

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            return ''; // non-null => Router stops pipeline
        }

        return null; // continue normal request
    }
}