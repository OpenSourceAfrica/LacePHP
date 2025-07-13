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

namespace Weave\Plugins\ShoeAI;

use Lacebox\Shoelace\CommandProviderInterface;
use Lacebox\Shoelace\PluginInterface;
use Lacebox\Shoelace\RouterInterface;
use Lacebox\Sole\Cli;
use Weave\Plugins\ShoeAI\Agents\Credentials;
use Weave\Plugins\ShoeAI\Agents\ShoeAIStatus;
use Weave\Plugins\ShoeAI\Agents\ShoeBuddy;
use Weave\Plugins\ShoeAI\Agents\ShoeGenie;

class AiCommands implements PluginInterface, CommandProviderInterface
{
    // --- from PluginInterface:
    public function register(RouterInterface $router, array $config): void
    {
        // optional—if you want to add routes
    }

    public function boot(array $config): void
    {
        // optional
    }

    // --- from CommandProviderInterface:
    public function registerCommands(Cli $cli): void
    {
        $cli->register(
            'ai:activate',
            'Activate your license',
            function($argv) {
                Credentials::enable();
            }
        );

        $cli->register(
            'ai:status',
            'Show your current AI subscription status',
            function($argv) {
                ShoeAIStatus::status();
            }
        );

        $cli->register(
            'ai:scaffold',
            'Let ShoeGenie scaffold a new API from a prompt',
            function($argv) {
                $prompt = $argv[2] ?? null;
                ShoeGenie::scaffold($prompt);
            }
        );

        $cli->register(
            'ai:rollback',
            'Rollback AI-generated code',
            function($argv) {
                ShoeGenie::rollback();
            }
        );

        $cli->register(
            'ai:buddy',
            'Ask ShoeBuddy to explain a line of code',
            function($argv) {
                $question = $argv[2] ?? null;
                ShoeBuddy::ask($question);
            }
        );
    }
}