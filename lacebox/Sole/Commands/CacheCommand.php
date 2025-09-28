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

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;
use Lacebox\Sole\Grip\CacheManager;
class CacheCommand implements CommandInterface
{
    public function name(): string
    {
        return 'cache';
    }

    public function description(): string
    {
        return 'Pruned expired cache files. Usage: php lace cache prune';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1], $argv[2])
            && $argv[1] === $this->name()
            && $argv[2] === 'prune';
    }

    public function run(array $argv): void
    {
        if ($argv[2] === 'prune') {
            echo "\nCleaning Cache files...\n";

            $driver = CacheManager::getInstance()->driver();
            if (method_exists($driver, 'purgeExpired')) {
                $removed = $driver->purgeExpired(10000, 10); // up to 10k files or 10s
                echo "Pruned {$removed} expired cache files.\n";
            } else {
                echo "Current cache driver manages expiration automatically.\n";
            }
        } else {
            echo "\n Usage: php lace cache prune\n";
        }
    }
}