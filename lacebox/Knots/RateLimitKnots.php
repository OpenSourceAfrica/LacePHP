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

/**
 * RateLimitKnots enforces a simple per-IP rate limit.
 */
class RateLimitKnots implements MiddlewareInterface
{
    /**
     * Maximum number of requests per window
     */
    protected $limit;

    /**
     * Window size in seconds
     */
    protected $window;

    public function __construct(int $limit = 60, int $window = 60)
    {
        $this->limit  = $limit;
        $this->window = $window;
    }

    public function handle(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$ip}";

        if (!isset($_SESSION[$key])) {
            //initialize counter and timestamp
            $_SESSION[$key] = ['count' => 1, 'start' => time()];
        } else {

            $data = &$_SESSION[$key];
            if (time() - $data['start'] < $this->window) {
                $data['count']++;
            } else {
                //reset window
                $data = ['count' => 1, 'start' => time()];
            }
            if ($data['count'] > $this->limit) {
                // Too many requests
                http_response_code(429);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Too Many Requests']);
                exit;
            }
        }
    }
}