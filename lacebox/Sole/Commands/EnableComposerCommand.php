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
use Lacebox\Sole\Config;

class EnableComposerCommand implements CommandInterface
{
    public function name(): string
    {
        return 'enable';
    }

    public function description(): string
    {
        return 'Enable Composer usage and install composer dependencies. Usage: php lace enable composer';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1], $argv[2])
            && $argv[1] === $this->name()
            && $argv[2] === 'composer';
    }

    public function run(array $argv): void
    {
        // Load the merged config singleton
        $config = Config::getInstance()->all();

        if (empty($config['cli']['allow_composer'] ?? false)) {
            fwrite(STDERR, "\n Composer usage is disabled in config.\n");
            exit(1);
        }

        echo "\n Installing Composer dependencies...\n";
        passthru('composer install');
    }
}