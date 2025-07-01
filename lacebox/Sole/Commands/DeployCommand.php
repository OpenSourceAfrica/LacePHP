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
use Lacebox\Sole\ShoeDeploy;

class DeployCommand implements CommandInterface
{
    public function name(): string
    {
        return 'deploy';
    }

    public function description(): string
    {
        return 'Ship your application different environment seamlessly. Usage: php lace deploy [env]';
    }

    public function matches(array $argv): bool
    {
        return ($argv[1] ?? null) === $this->name();
    }

    public function run(array $argv): void
    {
        $envName = $argv[2] ?? null;

        if ($envName) {
            ShoeDeploy::run($envName);
        } else {
            echo "\n❌ Usage:  php lace deploy [env]\n";
        }
    }
}