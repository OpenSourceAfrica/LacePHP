<?php
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