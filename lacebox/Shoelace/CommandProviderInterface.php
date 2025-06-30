<?php
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