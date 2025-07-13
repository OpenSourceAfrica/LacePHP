<?php

/**
 * LacePHP AI Plugin
 *
 * This plugin is part of the LacePHP framework.
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

namespace Weave\Plugins\ShoeAI\Agents;

class ShoeBuddy
{
    public static function ask(string $file, int $line, string $q): void
    {
        $cfg = config()['ai'] ?? [];
        if (empty($cfg['enabled'])) {
            fwrite(STDERR, ansi_color("AI disabled in config\n"));
            exit(1);
        }

        $client = new HttpClient();
        $resp   = $client->post('/buddy', [
            'file'     => $file,
            'line'     => $line,
            'question' => $q
        ]);

        if ($resp['status'] !== 200) {
            fwrite(STDERR, ansi_color("Buddy failed: {$resp['body']}\n"));
            exit(1);
        }

        $data  = json_decode($resp['body'], true);

        fwrite(STDERR, ansi_color($data['response'] . "\n"));
    }
}