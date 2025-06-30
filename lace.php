#!/usr/bin/env php
<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors','1');

//
// 1) Load your global helpers (autoload, config(), lace_hwid(), etc.)
//
require_once __DIR__ . '/lacebox/Sole/Helpers.php';

use Lacebox\Sole\PluginManager;
use Lacebox\Sole\Cli;
use Lacebox\Sole\Sockliner;

// 2) PSR-4 autoloader
enable_lace_autoloading();

// 3) Merge config from lace.json + config/lace.php + .env
$config = config();

// 4) (Optional) Composer autoload
if (! empty($config['cli']['allow_composer'])) {
    $vendorDir = rtrim($config['paths']['vendor'] ?? 'vendor','/');
    $autoload  = __DIR__ . "/{$vendorDir}/autoload.php";
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

// 5) Bootstrap your application (routes, DI, events, etc.)
$app    = Sockliner::getInstance();
$router = $app->getRouter();

// 6) Prepare the CLI
$cli = new Cli();

// 4) Auto-discover *all* classes under Lacebox\Sole\Commands\
$commands = [];
$cmdDir   = __DIR__ . '/lacebox/Sole/Commands';
foreach (glob($cmdDir . '/*.php') as $file) {
    require_once $file;
    $class = 'Lacebox\\Sole\\Commands\\' . basename($file, '.php');
    if (class_exists($class)) {
        $cmd = new $class($router, $app);
        if ($cmd instanceof \Lacebox\Shoelace\CommandInterface) {
            $cli->register(
                $cmd->name(),
                $cmd->description(),
                [$cmd, 'run']
            );
        }
    }
}

// … register other core commands here (deploy, ai:*, etc.)

// 8) Discover & register plugin‐provided commands
$pm = new PluginManager();
$pm->discoverFromFolder(__DIR__);
if (!empty($vendorDir)) {
    $pm->discoverFromComposer(__DIR__ . "/{$vendorDir}");
}
$pm->registerAll($router, $config);
foreach ($pm->getPlugins() as $plugin) {
    if ($plugin instanceof \Lacebox\Shoelace\CommandProviderInterface) {
        $plugin->registerCommands($cli);
    }
}

// 9) Hand off to the CLI
$cli->run($_SERVER['argv']);