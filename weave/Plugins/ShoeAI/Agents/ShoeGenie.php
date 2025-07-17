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
        $resp   = $client->post('/scaffold', [
            'prompt'  => $prompt,
            'hwid'    => lace_hwid($cfg['license_key']),
            'license' => $cfg['license_key'],
        ]);

        if ($resp['status'] !== 200) {
            fwrite(STDERR, "Scaffold failed: {$resp['body']}\n");
            exit(1);
        }

        $json = json_decode($resp['body'], true);
        if (! is_array($json)) {
            fwrite(STDERR, "Invalid JSON:\n{$resp['body']}\n");
            exit(1);
        }

        // 1) Plan all writes
        $planned    = [];
        $plannedFiles = [];

        foreach ($json as $key => $code) {
            //  a) AI returned a known role
            if (isset(self::$paths[$key])) {
                if ($key === 'routes') {
                    $relPath  = 'routes/api.php';
                    $fullPath = getcwd() . '/routes/api.php';
                } else {
                    $relPath  = self::$paths[$key] . '/' . self::detectName($code) . '.php';
                    $fullPath = dirname(__DIR__, 3) . '/' . $relPath;
                }
            }
            //  b) AI returned camel-case weave/Routes/... – treat as routes/api.php
            elseif (stripos($key, 'weave/Routes/') === 0) {
                $relPath  = 'routes/' . basename($key);
                $fullPath = getcwd() . '/' . $relPath;
            }
            //  c) AI returned a path-like key we don’t explicitly map
            elseif (strpos($key, '/') !== false && preg_match('/\.php$/', $key)) {
                $relPath  = ltrim($key, '/');
                $fullPath = getcwd() . '/' . $relPath;
            }
            else {
                fwrite(STDERR, "  • skipping unknown key “{$key}”\n");
                continue;
            }

            $planned[$relPath] = $code;
            $plannedFiles[]   = $fullPath;
        }

        if (empty($plannedFiles)) {
            fwrite(STDERR, "No files to write — aborting.\n");
            exit(1);
        }

        // 2) Show plan
        fwrite(STDOUT, "The AI scaffolder will now create or overwrite:\n\n");
        foreach ($plannedFiles as $f) {
            fwrite(STDOUT, "  - {$f}\n");
        }
        fwrite(STDOUT, "\nProceed? (y/N): ");
        $ans = trim(fgets(STDIN));
        if (! in_array(strtolower($ans), ['y','yes'], true)) {
            fwrite(STDOUT, "Aborted. No files were changed.\n");
            exit(0);
        }

        // 3) Write & manifest
        $manifest = [];
        foreach ($planned as $rel => $code) {
            $full = getcwd() . '/' . $rel;
            @mkdir(dirname($full), 0755, true);
            $manifest[$rel] = file_exists($full)
                ? file_get_contents($full)
                : null;
            file_put_contents($full, $code);
            fwrite(STDOUT, "Wrote {$full}\n");
        }

        file_put_contents(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));
        fwrite(STDOUT, "Scaffold complete. To undo, run: php lace ai:rollback\n");
    }

    public static function rollback(): void
    {
        if (! file_exists(self::MANIFEST)) {
            fwrite(STDERR, ansi_color("No scaffold manifest found; nothing to roll back.\n"));
            exit(1);
        }

        // determine project root
        $projectRoot = dirname(__DIR__, 4);

        $manifest = json_decode((string)file_get_contents(self::MANIFEST), true);
        foreach ($manifest as $relPath => $oldContent) {
            $full = "{$projectRoot}/{$relPath}";

            if ($oldContent === null) {
                // file was newly created — remove it
                if (is_file($full)) {
                    unlink($full);
                    fwrite(STDOUT, ansi_color("Deleted new file: {$relPath}\n"));
                }
            } else {
                // file existed before — restore previous content
                @file_put_contents($full, $oldContent);
                fwrite(STDOUT, ansi_color("Restored file: {$relPath}\n"));
            }
        }

        // cleanup
        unlink(self::MANIFEST);
        fwrite(STDOUT, ansi_color("Rollback complete.\n"));
    }

    protected static function detectName(string $code): string
    {
        if (preg_match('/(?:class|trait|interface)\s+([A-Za-z0-9_]+)/', $code, $m)) {
            return $m[1];
        }
        throw new \RuntimeException("Cannot detect PHP type name in scaffold code");
    }

}
