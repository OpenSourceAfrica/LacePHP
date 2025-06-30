<?php
namespace Weave\Plugins\ShoeAI\Agents;

use Lacebox\Sole\PluginManager;

class ServiceProvider
{
    public function register(PluginManager $pm): void
    {
        // register the 4 CLI commands
        $pm->registerCommands([
            \Weave\Plugins\ShoeAI\AiCommands::class,
        ]);
    }
}