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
use Lacebox\Tongue\TunnelService;

/**
 * ShareCommand exposes a local API over a secure ngrok tunnel.
 */
class ShareCommand implements CommandInterface
{
    public function name(): string
    {
        return 'dev:share';
    }

    public function description(): string
    {
        return 'Share local API over a secure tunnel via ngrok';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1]) && $argv[1] === $this->name();
    }

    public function run(array $argv): void
    {
        $port = 8000;
        if (isset($argv[2]) && is_numeric($argv[2])) {
            $port = (int)$argv[2];
        }

        echo "Starting ngrok tunnel on port {$port}..." . PHP_EOL;
        $service = new TunnelService($port);
        $service->share();
    }
}