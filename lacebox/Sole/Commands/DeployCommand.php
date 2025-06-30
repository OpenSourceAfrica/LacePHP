<?php

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;
use Lacebox\Sole\ShoeDeploy;

class DeployCommand implements CommandInterface
{
    public function name(): string
    {
        return 'deploy';
    }

    public function description(): string
    {
        return 'Ship your application different environment seamlessly. Usage: php lace deploy [env]';
    }

    public function matches(array $argv): bool
    {
        return ($argv[1] ?? null) === 'deploy';
    }

    public function run(array $argv): void
    {
        $envName = $argv[2] ?? null;

        if ($envName) {
            ShoeDeploy::run($envName);
        } else {
            echo "\n❌ Usage:  php lace deploy [env]\n";
        }
    }
}