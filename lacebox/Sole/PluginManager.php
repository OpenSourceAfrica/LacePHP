<?php
namespace Lacebox\Sole;

use Lacebox\Shoelace\PluginInterface;

class PluginManager
{
    /** @var PluginInterface[] */
    protected $plugins = [];

    /** @var string[] Fully-qualified CLI command classes */
    protected $commands = [];

    /**
     * Discover plugins in weave/Plugins (including subdirectories).
     *
     * @param string $projectRoot The project root (where `weave/Plugins` lives)
     */
    public function discoverFromFolder(string $projectRoot): void
    {
        $pluginsDir = rtrim($projectRoot, '/') . '/weave/Plugins';

        if (!is_dir($pluginsDir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginsDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            require_once $file->getPathname();

            // build the class name from the file's path relative to weave/Plugins
            $relative = substr($file->getPathname(), strlen($pluginsDir) + 1);
            // e.g. "FooPlugin.php" or "Sub/BarPlugin.php"
            $relative = str_replace('.php', '', $relative);
            // change directory separators to namespace separators
            $class = 'Weave\\Plugins\\' . str_replace('/', '\\', $relative);

            if (
                class_exists($class)
                && in_array(PluginInterface::class, class_implements($class), true)
            ) {
                $this->plugins[] = new $class();
            }
        }
    }

    /**
     * Discover plugins via composer packages (looking for lace-plugin.json).
     */
    public function discoverFromComposer(string $vendorDir): void
    {
        foreach (glob(rtrim($vendorDir,'/').'/*/*/lace-plugin.json') as $jsonFile) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (!empty($data['pluginClass']) && class_exists($data['pluginClass'])) {
                $this->plugins[] = new $data['pluginClass']();
            }
        }
    }

    /**
     * Let plugins register routes, middleware, service providers, etc.
     */
    public function registerAll($router, $config): void
    {
        if ($config instanceof Config) {
            $config = $config->all();
        }

        foreach ($this->plugins as $plugin) {
            $plugin->register($router, $config);
        }
    }

    /**
     * Let plugins perform any boot-time logic.
     */
    public function bootAll($config): void
    {
        if ($config instanceof Config) {
            $config = $config->all();
        }

        foreach ($this->plugins as $plugin) {
            $plugin->boot($config);
        }
    }

    /**
     * Give back the list for CLI wiring.
     *
     * @return PluginInterface[]
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Allow a plugin (or the app) to add CLI command classes.
     *
     * @param string[] $commandClasses Fully-qualified classnames that extend PluginCommand
     */
    public function registerCommands(array $commandClasses): void
    {
        foreach ($commandClasses as $c) {
            if (! in_array($c, $this->commands, true)) {
                $this->commands[] = $c;
            }
        }
    }

    /**
     * Retrieve all registered CLI command classes.
     *
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /** add a single provider instance */
    public function registerProvider(PluginInterface $provider): void
    {
        $this->plugins[] = $provider;
    }
}