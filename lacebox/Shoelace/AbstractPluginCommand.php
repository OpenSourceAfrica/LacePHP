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

namespace Lacebox\Shoelace;

use Lacebox\Sole\Cli;

abstract class AbstractPluginCommand implements PluginInterface
{
    public static function alias(): string
    {
        // default to class name without "Commands", lowercased
        $short = (new \ReflectionClass(static::class))
            ->getShortName();
        $short = preg_replace('/Commands$/', '', $short);
        return strtolower($short);
    }

    public static function description(): string
    {
        return static::alias() . ' plugin commands';
    }

    // subclasses must implement:
    abstract public function registerCommands(Cli $cli): void;
}