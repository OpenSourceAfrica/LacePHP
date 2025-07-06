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

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;

class DevWatchCommand implements CommandInterface
{
    public function name(): string
    {
        return 'dev';
    }

    public function description(): string
    {
        return 'Watch route files for changes. Usage: php lace dev watch';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1], $argv[2])
            && $argv[1] === $this->name()
            && $argv[2] === 'watch';
    }

    public function run(array $argv): void
    {
        echo "Watching route files for changes…\n";

        // Fetch route paths from config or default to ['routes']
        $paths = config('routing.route_paths', ['routes']);
        $dirs = [];
        foreach ($paths as $p) {
            // assume project root is two levels up
            $dirs[] = dirname(__DIR__, 3) . '/' . rtrim($p, '/');
        }

        // Build initial modification times
        $mtimes = [];
        foreach ($dirs as $dir) {
            foreach (glob($dir . '/*.php') as $f) {
                $mtimes[$f] = filemtime($f);
            }
        }

        // Polling loop
        while (true) {
            foreach ($dirs as $dir) {
                // New or modified files
                foreach (glob($dir . '/*.php') as $f) {
                    $time = filemtime($f);
                    if (!isset($mtimes[$f])) {
                        echo "New route file: {$f}\n";
                        $mtimes[$f] = $time;
                    } elseif ($time !== $mtimes[$f]) {
                        echo "Route file changed: {$f}\n";
                        $mtimes[$f] = $time;
                    }
                }
                // Deleted files
                foreach (array_keys($mtimes) as $f) {
                    if (strpos($f, $dir . '/') === 0 && !file_exists($f)) {
                        echo "Route file removed: {$f}\n";
                        unset($mtimes[$f]);
                    }
                }
            }
            sleep(1);
        }
    }
}