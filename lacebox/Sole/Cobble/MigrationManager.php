<?php
namespace Lacebox\Sole\Cobble;

class MigrationManager
{
    /**
     * Directory where migration files live (relative to project root).
     */
    protected static function migrationsDir(): string
    {
        return dirname(__DIR__, 3) . '/shoebox/migrations';
    }

    /**
     * Path to the JSON file tracking ran migrations.
     * Now lives inside shoebox/migrations/migrations.json
     */
    protected static function trackerFile(): string
    {
        return self::migrationsDir() . '/migrations.json';
    }

    /**
     * @return string[]  Fully-qualified class names already run
     */
    public static function getRan(): array
    {
        $file = self::trackerFile();
        if (! file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Mark a migration as run by appending its class name.
     */
    public static function markRan(string $class): void
    {
        $dir = self::migrationsDir();
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = self::trackerFile();
        $ran  = self::getRan();
        $ran[] = $class;
        $ran = array_values(array_unique($ran));
        file_put_contents($file, json_encode($ran, JSON_PRETTY_PRINT));
    }

    /**
     * Load and run all pending migration classes under shoebox/migrations/.
     */
    public static function runAll(): void
    {
        $dir = self::migrationsDir();
        if (! is_dir($dir)) {
            echo "ℹ️  No migrations directory found at {$dir}\n";
            return;
        }

        $ran = self::getRan();
        foreach (glob($dir . '/*.php') as $file) {
            require_once $file;
            $class = 'Shoebox\\Migrations\\' . basename($file, '.php');

            if (! class_exists($class) || in_array($class, $ran, true)) {
                continue;
            }

            $instance = new $class();
            if (! method_exists($instance, 'up')) {
                continue;
            }

            $instance->up();
            self::markRan($class);
            echo "✅ Ran migration: {$class}\n";
        }
    }
}