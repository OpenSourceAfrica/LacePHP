<?php

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;
use Lacebox\Sole\Sockliner;

class AppRunCommand implements CommandInterface
{
    public function name(): string
    {
        return 'app';
    }

    public function description(): string
    {
        return 'Run the App Instance';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1], $argv[2])
            && $argv[1] === 'app'
            && $argv[2] === 'run';
    }

    public function run(array $argv): void
    {
        echo "\n👟 Running application…\n";
        Sockliner::getInstance()->run();
    }
}