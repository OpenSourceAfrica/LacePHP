<?php
// lacebox/Sole/Commands/MetricsCommand.php
namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;

class MetricsCommand implements CommandInterface
{
    public function name(): string
    {
        return 'metrics';
    }

    public function description(): string
    {
        return 'Clear collected metrics. Usage: php lace metrics reset';
    }

    public function matches(array $argv): bool
    {
        return ($argv[1] ?? null) === 'metrics';
    }

    public function run(array $argv): void
    {
        $sub = $argv[2] ?? null;
        if ($sub === 'reset') {
            $file = __DIR__ . '/../../../shoebox/metrics/metrics.json';
            if (file_exists($file)) {
                unlink($file);
            }
            echo "✅ Metrics reset\n";
        } else {
            echo "\n❌ Usage:\n";
            echo "   php lace metrics reset   Clear all stored metrics\n";
        }
    }
}