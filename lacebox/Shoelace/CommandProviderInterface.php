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

namespace Lacebox\Shoelace;

use Lacebox\Sole\Cli;

/**
 * Optional interface: if your plugin wants to register console commands,
 * implement this alongside PluginInterface.
 */
interface CommandProviderInterface
{
    /**
     * Called early in the CLI bootstrap so you can `register()` subcommands.
     *
     * @param  Cli  $cli
     * @return void
     */
    public function registerCommands(Cli $cli): void;
}