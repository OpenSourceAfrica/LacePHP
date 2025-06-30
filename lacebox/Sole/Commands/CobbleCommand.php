<?php

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;
use Lacebox\Sole\Cobble\MigrationManager;

class CobbleCommand implements CommandInterface
{
    public function name(): string
    {
        return 'cobble';
    }

    public function description(): string
    {
        return 'Manage database migrations (create|run)';
    }

    public function matches(array $argv): bool
    {
        return ($argv[1] ?? null) === 'cobble';
    }

    public function run(array $argv): void
    {
        $action = $argv[2] ?? null;
        $name   = $argv[3] ?? null;

        if ($action === 'create') {
            if (!$name) {
                echo "\n❌ Migration name required. e.g. php lace cobble create AddUsersTable\n";
                exit(1);
            }
            $dir = __DIR__ . '/../../../shoebox/migrations';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $cleanName = preg_replace('/\W+/', '', ucfirst($name));
            $timestamp = date('Ymd_His');
            $class     = "{$cleanName}_{$timestamp}";
            $file      = "{$dir}/{$class}.php";
            if (file_exists($file)) {
                echo "\n⚠️  Migration already exists: {$file}\n";
                exit(1);
            }
            $stub = <<<PHP
<?php
namespace Shoebox\Migrations;

class {$class}
{
    public function up()
    {
        // TODO: \Lacebox\Sole\Cobble\ConnectionManager::getConnection()->exec(...);
    }
}
PHP;
            file_put_contents($file, $stub . "\n");
            echo "\n✅ Created migration stub: shoebox/migrations/{$class}.php\n";

        } elseif ($action === 'run') {
            MigrationManager::runAll();

        } else {
            echo "\n❌ Unknown cobble action: '{$action}'\n";
            echo "   Valid actions: create, run\n";
        }
    }
}