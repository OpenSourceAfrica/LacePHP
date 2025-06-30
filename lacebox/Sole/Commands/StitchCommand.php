<?php

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;

class StitchCommand implements CommandInterface
{
    public function name(): string
    {
        return 'stitch';
    }

    public function description(): string
    {
        return 'Scaffold route/controller/model/middleware';
    }

    public function matches(array $argv): bool
    {
        return ($argv[1] ?? null) === 'stitch';
    }

    public function run(array $argv): void
    {
        $what = $argv[2] ?? null;
        $name = $argv[3] ?? null;
        if (!$what || !$name) {
            echo "\n❌ Usage: php lace stitch [route|controller|model|middleware] Name\n";
            exit(1);
        }
        switch ($what) {
            case 'route':
                $file = __DIR__ . '/../../../routes/' . $name . '.php';
                if (file_exists($file)) {
                    echo "\n⚠️  Route file already exists: {$file}\n";
                    exit(1);
                }
                $uri  = strtolower($name);
                $stub = <<<PHP
<?php

use Weave\Controllers\\{$name}Controller;

\$router->get('/{$uri}', [{$name}Controller::class, 'index']);
PHP;
                file_put_contents($file, $stub . PHP_EOL);
                echo "\n✅ Route scaffold created at {$file}\n";
                break;

            case 'controller':
                // … (same pattern for controller, model, middleware)
                break;

            default:
                echo "\n❌ Unknown stitch type: {$what}\n";
                echo "   Valid types: route, controller, model, middleware\n";
        }
    }
}