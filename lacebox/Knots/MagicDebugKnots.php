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
            $method = sole_request()->method();
            $uri    = UriResolver::resolve();
            DebugCollector::add("[START] {$method} {$uri}");

            register_shutdown_function(function() use ($method, $uri) {
                $mem = memory_get_usage(true);
                DebugCollector::add("[END] {$method} {$uri} - memory_usage={$mem}");
            });
        }
    }
}