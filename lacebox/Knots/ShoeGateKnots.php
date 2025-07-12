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
use Lacebox\Sole\UriResolver;

class ShoeGateKnots implements MiddlewareInterface
{
    protected $cfg;

    public function __construct()
    {
        $this->cfg = config()['ip'] ?? [];
    }

    public function handle(): void
    {
        // Get the client IP
        $ip = sole_request()->ip();

        // 1) Check global blacklist
        foreach ($this->cfg['blacklist'] ?? [] as $blocked) {
            if ($this->matches($ip, $blocked)) {
                $this->deny("Your IP ({$ip}) is blacklisted");
            }
        }

        // 2) Check global whitelist
        if (! empty($this->cfg['whitelist'] ?? [])) {
            $ok = false;
            foreach ($this->cfg['whitelist'] as $allowed) {
                if ($this->matches($ip, $allowed)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                $this->deny("Your IP ({$ip}) is not whitelisted");
            }
        }

        // 3) Route-specific overrides
        $uri = UriResolver::resolve(); // e.g. "/admin"
        $routes = $this->cfg['routes'] ?? [];
        if (isset($routes[$uri])) {
            $r = $routes[$uri];

            // route blacklist
            foreach ($r['blacklist'] ?? [] as $blocked) {
                if ($this->matches($ip, $blocked)) {
                    $this->deny("Your IP ({$ip}) is blacklisted for {$uri}");
                }
            }

            // route whitelist
            if (! empty($r['whitelist'] ?? [])) {
                $ok = false;
                foreach ($r['whitelist'] as $allowed) {
                    if ($this->matches($ip, $allowed)) {
                        $ok = true;
                        break;
                    }
                }
                if (! $ok) {
                    $this->deny("Your IP ({$ip}) is not allowed for {$uri}");
                }
            }
        }

        // otherwise, pass through
    }

    /**
     * Match an IP against either a single IP or a CIDR block.
     */
    protected function matches(string $ip, string $pattern): bool
    {
        // CIDR?
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern, 2);
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $this->inRangeV4($ip, $subnet, (int)$mask);
            }
            // you can add IPv6/CIDR support here if desired
            return false;
        }
        // exact IP
        return $ip === $pattern;
    }

    /**
     * IPv4 in CIDR check.
     */
    protected function inRangeV4(string $ip, string $subnet, int $mask): bool
    {
        $ip = ip2long($ip);
        $sn = ip2long($subnet);
        $maskBin = ~((1 << (32 - $mask)) - 1);
        return ($ip & $maskBin) === ($sn & $maskBin);
    }

    /**
     * Deny access immediately.
     */
    protected function deny(string $message): void
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Forbidden', 'message' => $message]);
        exit;
    }
}