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

class TreadCommand implements CommandInterface
{
    public function name(): string
    {
        return 'tread';
    }

    public function description(): string
    {
        return 'Tread the dev server (built-in PHP). Usage: php lace tread [host:port]';
    }

    public function matches(array $argv): bool
    {
        // php lace tread or php lace tread 127.0.0.1:9000
        return isset($argv[1]) && $argv[1] === $this->name();
    }

    public function run(array $argv): void
    {
        // figure out host:port
        $addr = $argv[2] ?? '127.0.0.1:6916';

        // point document root at public/
        $base   = realpath(__DIR__ . '/../../../');
        $docRoot= $base . '/public';
        $router = $base . '/toebox.php';

        if (! is_dir($docRoot) || ! file_exists($router)) {
            fwrite(STDERR, "Make sure you have public/ and toebox.php in your project root\n");
            exit(1);
        }

        echo "\nTreading dev server at http://{$addr}\n";
        echo "Document root is {$docRoot}\n\n";

        // hand off to PHP built-in server
        // this will block until you Ctrl+C
        passthru(sprintf(
            'php -S %s -t %s %s',
            escapeshellarg($addr),
            escapeshellarg($docRoot),
            escapeshellarg($router)
        ));
    }
}
