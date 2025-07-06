<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Sole;

class Env
{
    /** @var array<string,string>|null */
    private static $vars = null;

    /** Load and cache the .env file */
    private static function load(): void
    {
        if (self::$vars !== null) {
            return;
        }

        self::$vars = [];
        $path = dirname(__DIR__, 2) . '/.env';

        if (! file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            // key=value (stripping any surrounding quotes)
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            self::$vars[$key] = $value;
        }
    }

    /**
     * Get one variable
     */
    public static function get(string $key, $default = null)
    {
        self::load();
        return self::$vars[$key] ?? $default;
    }

    /**
     * Get the entire raw array
     * (useful when you need to iterate all .env keys)
     *
     * @return array<string,string>
     */
    public static function all(): array
    {
        self::load();
        return self::$vars;
    }
}