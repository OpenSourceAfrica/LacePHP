<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
        return 'Scaffold what you need. Usage: php lace stitch (route | controller | model | middleware) Name';
    }

    public function matches(array $argv): bool
    {
        return ($argv[1] ?? null) === $this->name();
    }

    public function run(array $argv): void
    {
        $what = $argv[2] ?? null;
        $name = $argv[3] ?? null;
        if (!$what || !$name) {
            echo "\n❌ Usage: php lace stitch (route | controller | model | middleware) Name\n";
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
                $dir  = __DIR__ . '/weave/Controllers';
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $file = "{$dir}/{$name}Controller.php";
                if (file_exists($file)) {
                    echo "\n⚠️  Controller already exists: {$file}\n";
                    exit(1);
                }
                $stub = <<<PHP
<?php

namespace Weave\Controllers;

class {$name}Controller
{
    public function index()
    {
        return ['message' => 'Hello from {$name}Controller'];
    }
}
PHP;
                file_put_contents($file, $stub . PHP_EOL);
                echo "\n✅ Controller scaffold created at {$file}\n";
                break;

            case 'model':
                $dir  = __DIR__ . '/weave/Models';
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $file = "{$dir}/{$name}.php";
                if (file_exists($file)) {
                    echo "\n⚠️  Model already exists: {$file}\n";
                    exit(1);
                }
                $stub = <<<PHP
<?php

namespace Weave\Models;

class {$name}
{
    // TODO: define model properties and methods
}
PHP;
                file_put_contents($file, $stub . PHP_EOL);
                echo "\n✅ Model scaffold created at {$file}\n";
                break;

            case 'middleware':
                $dir       = __DIR__ . '/weave/Middlewares';
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Suffix the name so class is FooMiddleware
                $className = "{$name}Middleware";
                $file      = "{$dir}/{$className}.php";

                if (file_exists($file)) {
                    echo "\n⚠️  Middleware already exists: {$file}\n";
                    exit(1);
                }

                $stub = <<<PHP
<?php

namespace Weave\Middlewares;

use Lacebox\Shoelace\MiddlewareInterface;

class {$className} implements MiddlewareInterface
{
    public function handle(): void
    {
        // TODO: implement middleware logic
    }
}
PHP;

                file_put_contents($file, $stub . PHP_EOL);
                echo "\n✅ Middleware scaffold created at {$file}\n";
                break;

            default:
                echo "\n❌ Unknown stitch type: {$what}\n";
                echo "   Valid types: route, controller, model, middleware\n";
        }
    }
}