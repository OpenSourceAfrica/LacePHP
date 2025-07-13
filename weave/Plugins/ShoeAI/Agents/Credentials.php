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

class Credentials
{
    const CONFIG_KEY = 'ai.token';

    public static function enable(): void
    {
        // 0) Load the merged config
        $cfg = config();

        // 1) If we already have a license_key and enabled=true, bail out
        if (! empty($cfg['ai']['enabled']) && ! empty($cfg['ai']['license_key'])) {
            fwrite(STDOUT, ansi_color("You’re already activated (license: {$cfg['ai']['license_key']}).\n"));
            return;
        }

        fwrite(STDOUT, "Your License Key: ");
        $license_key = trim(fgets(STDIN));

        // call your registration endpoint
        $http = new HttpClient();
        $resp = $http->post('/activate', [
            'hwid'    => lace_hwid($license_key),
            'license' => $license_key,
            'version' => config('sole_version'),
        ]);

        if ($resp['status'] !== 200) {
            fwrite(STDERR, ansi_color("Activation failed: {$resp['body']}\n"));
            exit(1);
        }

        $data  = json_decode($resp['body'], true);
        $token = $data['token'] ?? null;
        if (! $token) {
            fwrite(STDERR, ansi_color("No token returned\n"));
            exit(1);
        }

        // persist into lace.json under "ai.token"
        $cfg = self::laceJsonConfig();

        $cfg['ai']['enabled'] = true;
        $cfg['ai']['license_key']   = $license_key;
        $cfg['ai']['token']   = $token;
        file_put_contents(
            getcwd() . '/lace.json',
            json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
        );

        fwrite(STDOUT, ansi_color("AI plugin enabled. Token saved.\n"));
    }

    public static function token(): ?string
    {
        return config(self::CONFIG_KEY, null);
    }

    private static function laceJsonConfig(): array
    {
        $path = getcwd() . '/lace.json';
        if (! file_exists($path)) {
            throw new \RuntimeException("lace.json not found at {$path}");
        }

        $contents = file_get_contents($path);
        return json_decode($contents, true);
    }
}