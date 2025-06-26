<?php
namespace Lacebox\Sole;

use Lacebox\Shoelace\PluginInterface;

class PluginManager
{
    protected $plugins = [];

    /**
     * Discover plugins in weave/Plugins.
     */
    public function discoverFromFolder(string $baseDir): void
    {
        $dir = $baseDir . '/weave/Plugins';
        foreach (glob($dir . '/*.php') as $file) {
            require_once $file;
            $class = 'Weave\\Plugins\\' . basename($file, '.php');
            if (
                class_exists($class) &&
                in_array(PluginInterface::class, class_implements($class), true)
            ) {
                $this->plugins[] = new $class();
            }
        }
    }

    /**
     * Optionally load plugins from composer-installed packages by scanning
     * vendor lace-plugin.json and reading their "pluginClass".
    */
    public function discoverFromComposer(string $vendorDir): void
    {
        foreach (glob($vendorDir . '/*/*/lace-plugin.json') as $jsonFile) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (!empty($data['pluginClass']) && class_exists($data['pluginClass'])) {
                $this->plugins[] = new $data['pluginClass']();
            }
        }
    }

    /**
     * Register all discovered plugins.
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
     * Boot all discovered plugins.
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
}