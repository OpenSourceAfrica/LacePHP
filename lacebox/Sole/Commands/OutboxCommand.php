<?php

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;

class OutboxCommand implements CommandInterface
{
    public function name(): string
    {
        return 'outbox';
    }

    public function description(): string
    {
        return 'Create a symlink from shoebox/outbox to public/outbox';
    }

    public function matches(array $argv): bool
    {
        return isset($argv[1]) && $argv[1] === 'outbox';
    }

    public function run(array $argv): void
    {
        $sub = $argv[2] ?? null;

        if ($sub === 'link') {
            $cwd    = getcwd();
            $target = $cwd . '/shoebox/outbox';
            $link   = $cwd . '/public/outbox';

            // Ensure target exists
            if (!is_dir($target)) {
                echo "🛠  Directory does not exist, creating: shoebox/outbox\n";
                if (!mkdir($target, 0755, true) && !is_dir($target)) {
                    echo "❌  Failed to create directory: shoebox/outbox\n";
                    return;
                }
            }

            // Prevent overwriting existing link or folder
            if (file_exists($link) || is_link($link)) {
                echo "❌  public/outbox already exists. Remove it first to recreate the link.\n";
                return;
            }

            // Create the symlink
            if (symlink($target, $link)) {
                echo "🔗  Symlink created: public/outbox → shoebox/outbox\n";
            } else {
                echo "❌  Failed to create symlink. Check permissions and paths.\n";
            }
        } else {
            echo "\n❌  Usage:\n";
            echo "   php lace outbox link   Create the public/outbox symlink\n";
            echo "\n";
        }
    }
}