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

use Lacebox\Sole\MetricsCollector;

class Metrics
{
    /**
     * Show Prometheus‐style metrics.
     *
     * @return string  The response body
     */
    public function show(): string
    {
        // We still need to set the Content-Type
        header('Content-Type: text/plain; version=0.0.4');

        // Build up the output rather than echo directly
        $lines = [];

        // Counters
        foreach (MetricsCollector::getCounters() as $name => $val) {
            $lines[] = "{$name} {$val}";
        }

        // Timings: summary_count, summary_sum, summary_max
        foreach (MetricsCollector::getTimings() as $key => $stat) {
            $lines[] = "{$key}_count {$stat['cnt']}";
            $lines[] = "{$key}_sum "   . ($stat['avg'] * $stat['cnt']);
            $lines[] = "{$key}_max {$stat['max']}";
        }

        // Join with newline and final newline
        return implode("\n", $lines) . "\n";
    }
}