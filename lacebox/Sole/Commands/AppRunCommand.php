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
use Lacebox\Sole\Sockliner;

class AppRunCommand implements CommandInterface
{
    public function name(): string
    {
        return 'app';
    }

    public function description(): string
    {
        return 'Running application. Usage: php lace app run';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1], $argv[2])
            && $argv[1] === $this->name()
            && $argv[2] === 'run';
    }

    public function run(array $argv): void
    {
        if ($argv[2] === 'run') {
            echo "\n Running application...\n";
            Sockliner::getInstance()->run();
        } else {
            echo "\n Usage: php lace app run\n";
        }
    }
}