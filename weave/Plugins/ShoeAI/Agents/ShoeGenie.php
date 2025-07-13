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

        if (! $prompt) {
            fwrite(STDOUT, ansi_color("Describe the API you want:\n> "));
            $prompt = trim(fgets(STDIN));
        }

        $client = new HttpClient();
        $resp   = $client->post('/scaffold', ['prompt' => $prompt]);
        if ($resp['status'] !== 200) {
            fwrite(STDERR, ansi_color("Scaffold failed: {$resp['body']}\n"));
            exit(1);
        }

        $json = json_decode($resp['body'], true);
        if (! is_array($json)) {
            fwrite(STDERR, ansi_color("Invalid JSON:\n{$resp['body']}\n"));
            exit(1);
        }


        // 1) Build a list of all planned writes
        $planned = [];
        $full = '';

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
                    fwrite(STDERR, ansi_color("Could not determine name for {$role}\n"));
                    continue;
                }

                if (! isset(self::$paths[$role])) {
                    throw new \InvalidArgumentException("Unknown role “{$role}”");
                }

                $filename = self::$paths[$role] . '/' . $name . '.php';
            }

            $full = "{$baseDir}/{$filename}";
            $planned[$role][] = $full;
        }

        // 2) Show the user what will happen
        fwrite(STDOUT, ansi_color("The AI scaffolder will now create or overwrite the following files:\n\n"));
        foreach ($planned as $role => $files) {
            fwrite(STDOUT, ansi_color(strtoupper($role) . ":\n"));
            foreach ($files as $f) {
                fwrite(STDOUT, ansi_color("  - {$f}\n"));
            }
            fwrite(STDOUT, ansi_color("\n"));
        }
        fwrite(STDOUT, ansi_color("Proceed? (y/N): "));

        // 3) Read confirmation
        $answer = trim(fgets(STDIN));
        if (! in_array(strtolower($answer), ['y','yes'], true)) {
            fwrite(STDOUT, ansi_color("Aborted. No files were changed.\n"));
            exit(0);
        }

        // 4) Actually write the files and build manifest
        $manifest = [];
        foreach ($json as $role => $code) {
            if (! isset(self::$paths[$role])) {
                continue;
            }
            // … same logic to compute $full …
            @mkdir(dirname($full), 0755, true);

            // record old content
            $manifest[$role . '/' . basename($full)] = file_exists($full)
                ? file_get_contents($full)
                : null;

            file_put_contents($full, $code);
            fwrite(STDOUT, ansi_color("Wrote {$full}\n"));
        }

        // 5) Save manifest and finish
        file_put_contents(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));
        fwrite(STDOUT, ansi_color("Scaffold complete. To undo, run: php lace ai:rollback\n"));
    }

    public static function rollback(): void
    {
        if (! file_exists(self::MANIFEST)) {
            fwrite(STDERR, ansi_color("No scaffold manifest found; nothing to roll back.\n"));
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
                    fwrite(STDOUT, ansi_color("Deleted new file: {$role}/{$filename}\n"));
                }
            } else {
                // file existed before — restore previous content
                file_put_contents($full, $oldContent);
                fwrite(STDOUT, ansi_color("Restored file: {$role}/{$filename}\n"));
            }
        }

        unlink(self::MANIFEST);
        fwrite(STDOUT, ansi_color("Rollback complete.\n"));
    }
}
