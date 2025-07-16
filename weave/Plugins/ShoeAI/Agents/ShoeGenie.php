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
            fwrite(STDERR, ansi_color("AI disabled in config\n"));
            exit(1);
        }

        // Prompt if needed
        if (! $prompt) {
            fwrite(STDOUT, ansi_color("Describe the API you want:\n> "));
            $prompt = trim(fgets(STDIN));
        }

        // Call the AI service
        $client = new HttpClient();
        $resp   = $client->post('/scaffold', [
            'prompt'  => $prompt,
            'hwid'    => lace_hwid($cfg['license_key']),
            'license' => $cfg['license_key'],
        ]);

        if ($resp['status'] !== 200) {
            fwrite(STDERR, ansi_color("Scaffold failed: {$resp['body']}\n"));
            exit(1);
        }

        $json = json_decode($resp['body'], true);
        if (! is_array($json)) {
            fwrite(STDERR, ansi_color("Invalid JSON:\n{$resp['body']}\n"));
            exit(1);
        }

        // 1) Build and display the plan
        $planned = [];
        fwrite(STDOUT, ansi_color("AI returned roles: " . implode(', ', array_keys($json)) . "\n\n"));

        foreach ($json as $key => $code) {
            // 1) if the key matches one of our roles, build its target path
            if (isset(self::$paths[$key])) {

                if ($key === 'routes') {
                    // projectRoot/routes instead of projectRoot/weave/routes
                    $baseDir = getcwd() . '/routes';
                    $filename = 'api.php';
                    $relPath  = 'routes/' . $filename;
                } else {
                    $baseDir = dirname(__DIR__, 3) . '/' . self::$paths[$key];
                    $filename = ($key === 'routes')
                        ? 'api.php'
                        : (preg_match('/(?:class|trait|interface)\s+(\w+)/i', $code, $m)
                            ? $m[1] . '.php'
                            : null
                        );
                    if (! $filename) {
                        fwrite(STDERR, ansi_color("Could not detect class name for “{$key}”\n"));
                        continue;
                    }
                    $relPath = self::$paths[$key] . '/' . $filename;
                }
            }
            // 2) otherwise if the key looks like a path (contains “/” and ends in .php), use it directly
            elseif (strpos($key, '/') !== false && preg_match('/\.php$/i', $key)) {
                $relPath = ltrim($key, '/');
                // make sure the leading directory exists
                $baseDir = dirname(getcwd() . '/' . $relPath);
            }
            else {
                fwrite(STDERR, ansi_color("  • skipping unknown key “{$key}”\n"));
                continue;
            }

            // final absolute path
            $full = getcwd() . '/' . $relPath;
            $planned[$relPath] = $code;
            $plannedFiles[]    = $full;
        }

        if (empty($planned)) {
            fwrite(STDERR, ansi_color("No files to write — aborting.\n"));
            exit(1);
        }

        fwrite(STDOUT, ansi_color("The AI scaffolder will now create or overwrite:\n\n"));
        foreach ($plannedFiles as $full) {
            fwrite(STDOUT, ansi_color("  - {$full}\n"));
        }

        fwrite(STDOUT, ansi_color("Proceed? (y/N): "));

        $answer = trim(fgets(STDIN));
        if (! in_array(strtolower($answer), ['y','yes'], true)) {
            fwrite(STDOUT, ansi_color("Aborted. No files were changed.\n"));
            exit(0);
        }

        // 2) Write them and build manifest
        $manifest = [];
        foreach ($planned as $relPath => $code) {
            $full = getcwd() . '/' . $relPath;
            @mkdir(dirname($full), 0755, true);
            $manifest[$relPath] = file_exists($full)
                ? file_get_contents($full)
                : null;
            file_put_contents($full, $code);
            fwrite(STDOUT, ansi_color("Wrote {$full}\n"));
        }

        // 3) Save manifest
        file_put_contents(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));
        fwrite(STDOUT, ansi_color("Scaffold complete. To undo, run: php lace ai:rollback\n"));
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

}