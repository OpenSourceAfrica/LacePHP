<?php
namespace Lacebox\Tongue;

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

class TunnelService
{
    protected $port;

    public function __construct(int $port = 8000)
    {
        $this->port = $port;
    }

    public function share(): void
    {
        exec(
            sprintf('ngrok http %d --log=stdout > /dev/null & echo $!', $this->port),
            $pidOutput
        );
        sleep(2);

        $tunnels = @json_decode(
            file_get_contents('http://127.0.0.1:4040/api/tunnels'),
            true
        );
        $url = $tunnels['tunnels'][0]['public_url'] ?? 'unknown';

        echo "Public URL: {$url}" . PHP_EOL;
    }
}