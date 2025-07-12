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

class ShoeGenie
{
    private const MANIFEST = __DIR__ . '/../scaffold-manifest.json';

    /** where each top‐level key should live */
    private static $paths = [
        'migration'  => 'shoebox/migrations',
        'model'      => 'weave/Models',
        'controller' => 'weave/Controllers',
        'routes'     => 'routes', // we'll always write to routes/api.php
        'plugin'      => 'weave/Plugins',
        'library'     => 'weave/Libraries',
        'middleware'  => 'weave/Middlewares',
        'validator'   => 'weave/Validators',
    ];

    public static function scaffold(?string $prompt): void
    {
        $cfg = config()['ai'] ?? [];
        if (empty($cfg['enabled'])) {
            fwrite(STDERR, "AI disabled in config\n");
            exit(1);
        }

        if (! $prompt) {
            fwrite(STDOUT, "Describe the API you want:\n> ");
            $prompt = trim(fgets(STDIN));
        }

        $client = new HttpClient();
        $resp   = $client->post('/scaffold', ['prompt' => $prompt]);
        if ($resp['status'] !== 200) {
            fwrite(STDERR, "Scaffold failed: {$resp['body']}\n");
            exit(1);
        }

        $json = json_decode($resp['body'], true);
        if (! is_array($json)) {
            fwrite(STDERR, "Invalid JSON:\n{$resp['body']}\n");
            exit(1);
        }

        $manifest = [];

        foreach ($json as $role => $code) {
            if (! isset(self::$paths[$role])) {
                // unknown chunk – skip
                continue;
            }

            $baseDir = dirname(__DIR__, 3) . '/' . self::$paths[$role];

            if ($role === 'routes') {
                $filename = 'api.php';
            } else {

                // pull out the PHP class name (or interface/trait)
                if (preg_match('/^\s*namespace\s+[^;]+;.*class\s+([A-Za-z0-9_]+)/s', $code, $m)) {
                    $name = $m[1];
                } elseif (preg_match('/^\s*namespace\s+[^;]+;.*trait\s+([A-Za-z0-9_]+)/s', $code, $m)) {
                    $name = $m[1];
                } elseif (preg_match('/^\s*namespace\s+[^;]+;.*interface\s+([A-Za-z0-9_]+)/s', $code, $m)) {
                    $name = $m[1];
                } else {
                    fwrite(STDERR, "Could not determine name for {$role}\n");
                    continue;
                }

                if (! isset(self::$paths[$role])) {
                    throw new \InvalidArgumentException("Unknown role “{$role}”");
                }

                $filename = self::$paths[$role] . '/' . $name . '.php';
            }

//            $full = "{$baseDir}/{$filename}";
//            @mkdir(dirname($full), 0755, true);
//
//            // record previous contents
//            $manifest["{$role}/{$filename}"] = file_exists($full)
//                ? file_get_contents($full)
//                : null;
//
//            file_put_contents($full, $code);
//            fwrite(STDOUT, "Wrote {$role}/{$filename}\n");

        }

        // save the manifest so we can undo later
        file_put_contents(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));

        fwrite(STDOUT, "Scaffold complete.\n");
        fwrite(STDOUT, "To undo, run: php lace ai:rollback\n");
    }

    public static function rollback(): void
    {
        if (! file_exists(self::MANIFEST)) {
            fwrite(STDERR, "No scaffold manifest found; nothing to roll back.\n");
            exit(1);
        }

        $manifest = json_decode(file_get_contents(self::MANIFEST), true);
        foreach ($manifest as $rel => $oldContent) {
            [$role, $filename] = explode('/', $rel, 2);
            $full = dirname(__DIR__, 3) . '/' . self::$paths[$role] . '/' . $filename;

            if ($oldContent === null) {
                // file was newly created — remove it
                if (file_exists($full)) {
                    unlink($full);
                    fwrite(STDOUT, "Deleted new file: {$role}/{$filename}\n");
                }
            } else {
                // file existed before — restore previous content
                file_put_contents($full, $oldContent);
                fwrite(STDOUT, "Restored file: {$role}/{$filename}\n");
            }
        }

        unlink(self::MANIFEST);
        fwrite(STDOUT, "Rollback complete.\n");
    }
}
