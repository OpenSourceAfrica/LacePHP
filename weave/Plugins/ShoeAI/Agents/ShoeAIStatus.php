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

class ShoeAIStatus
{
    public static function status(): void
    {
        $cfg = config()['ai'] ?? [];
        if (empty($cfg['enabled'])) {
            fwrite(STDERR, ansi_color("AI disabled in config\n"));
            exit(1);
        }

        $http = new HttpClient();
        $resp = $http->post('/status', [
            'hwid' => lace_hwid($cfg['enabled']['license_key']),
        ]);

        fwrite(STDERR, ansi_color("{$resp['body']}\n"));
    }
}