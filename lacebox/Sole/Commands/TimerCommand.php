<?php

namespace Lacebox\Sole\Commands;

use Lacebox\Shoelace\CommandInterface;
use Lacebox\Sole\LaceTimer;

class TimerCommand implements CommandInterface
{
    public function name(): string
    {
        return 'timer';
    }

    public function description(): string
    {
        return 'Manage scheduled tasks (list|run)';
    }

    public function matches(array $argv): bool
    {
        return ($argv[1] ?? null) === 'timer';
    }

    public function run(array $argv): void
    {
        $sub   = $argv[2] ?? null;
        $timer = new LaceTimer();
        if ($sub === 'list') {
            $all = $timer->loadSchedule();
            if (function_exists('schedule')) {
                $all = array_merge($all, schedule()->getCodeTasks());
            }
            echo "\n🗓  Scheduled tasks:\n";
            foreach ($all as $t) {
                printf("  %-20s  %-11s  %s\n",
                    $t['name'], $t['cron'], $t['handler']
                );
            }
            echo "\n";

        } elseif ($sub === 'run') {
            $timer->runDue();

        } else {
            echo "\n❌ Usage:\n";
            echo "   php lace timer list   List all scheduled tasks\n";
            echo "   php lace timer run    Run due tasks now\n";
        }
    }
}