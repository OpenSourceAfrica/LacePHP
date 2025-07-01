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

namespace Lacebox\Knots;

use Lacebox\Shoelace\MiddlewareInterface;

class RecorderKnots implements MiddlewareInterface
{
    public function handle($request, $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $end   = microtime(true);

        $record = [
            'ts'       => $start,
            'method'   => $_SERVER['REQUEST_METHOD'],
            'uri'      => $_SERVER['REQUEST_URI'],
            'request'  => [
                'headers' => getallheaders(),
                'body'    => file_get_contents('php://input'),
            ],
            'response' => [
                'status'  => http_response_code(),
                'headers' => headers_list(),
                'body'    => ob_get_contents(),
            ],
            'duration' => $end - $start,
        ];

        file_put_contents(
            storage_path('dev/records.json'),
            json_encode($record) . "\n",
            FILE_APPEND|LOCK_EX
        );

        return $response;
    }
}
