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

class OutsoleCommand implements CommandInterface
{
    public function name(): string
    {
        return 'outsole';
    }

    public function description(): string
    {
        return 'Create a symlink from shoebox/outsole to public/outsole. Usage: php lace outsole link';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1]) && $argv[1] === 'outsole';
    }

    public function run(array $argv): void
    {
        $sub = $argv[2] ?? null;

        if ($sub === 'link') {
            $cwd    = getcwd();
            $target = $cwd . '/shoebox/outsole';
            $link   = $cwd . '/public/outsole';

            // Ensure target exists
            if (!is_dir($target)) {
                echo "🛠  Directory does not exist, creating: shoebox/outsole\n";
                if (!mkdir($target, 0755, true) && !is_dir($target)) {
                    echo "❌  Failed to create directory: shoebox/outsole\n";
                    return;
                }
            }

            // Prevent overwriting existing link or folder
            if (file_exists($link) || is_link($link)) {
                echo "❌  public/outsole already exists. Remove it first to recreate the link.\n";
                return;
            }

            // Create the symlink
            if (symlink($target, $link)) {
                echo "🔗  Symlink created: public/outsole → shoebox/outsole\n";
            } else {
                echo "❌  Failed to create symlink. Check permissions and paths.\n";
            }
        } else {
            echo "\n❌  Usage:\n";
            echo "   php lace outsole link   Create the public/outsole symlink\n";
            echo "\n";
        }
    }
}