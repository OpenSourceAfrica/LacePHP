<?php
namespace Lacebox\Sole;

class LaceTimer
{
    /**
     * Where to find schedule.json
     */
    protected static function scheduleDir(): string
    {
        return dirname(__DIR__, 2) . '/aglet';
    }

    protected static function scheduleFile(): string
    {
        return self::scheduleDir() . '/schedule.json';
    }

    /**
     * Load raw JSON schedule definitions.
     *
     * @return array{ name:string, cron:string, handler:string }[]
     */
    public function loadSchedule(): array
    {
        echo $dir  = self::scheduleDir();
        $file = self::scheduleFile();

        // ensure the folder exists
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // *** load code‐based tasks automatically ***
        $kernel = $dir . '/kernel.php';
        if (file_exists($kernel)) {
            require_once $kernel;
        }

        if (! file_exists($file)) {
            return [];
        }
        $json = json_decode(file_get_contents($file), true);
        return is_array($json) ? $json : [];
    }


    /**
     * Which tasks are due at this tick?
     */
    public function dueTasks(): array
    {
        $now = time();
        $due = [];

        foreach ($this->loadSchedule() as $task) {
            if ($this->isDue($task['cron'], $now)) {
                $due[] = $task;
            }
        }

        // also include any code-based registrations
        if (function_exists('schedule')) {
            foreach (schedule()->getCodeTasks() as $task) {
                if ($this->isDue($task['cron'], $now)) {
                    $due[] = $task;
                }
            }
        }

        return $due;
    }

    /**
     * Cron-match against a timestamp.
     */
    protected function isDue(string $expr, int $ts): bool
    {
        list($min, $hour, $mday, $mon, $wday) = preg_split('/\s+/', $expr);
        $dt = getdate($ts);

        return $this->matchCron($min,   $dt['minutes'])
            && $this->matchCron($hour,  $dt['hours'])
            && $this->matchCron($mday,  $dt['mday'])
            && $this->matchCron($mon,   $dt['mon'])
            && $this->matchCron($wday,  $dt['wday']);
    }

    protected function matchCron(string $field, int $value): bool
    {
        if ($field === '*') {
            return true;
        }
        foreach (explode(',', $field) as $part) {
            if (strpos($part, '-') !== false) {
                list($start, $end) = explode('-', $part, 2);
                if ($value >= (int)$start && $value <= (int)$end) {
                    return true;
                }
            } elseif ((int)$part === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Run all due tasks.
     */
    public function runDue(): void
    {
        $due = $this->dueTasks();
        if (empty($due)) {
            echo "🕒 No tasks due right now.\n";
            return;
        }

        foreach ($due as $task) {
            echo "🔄 Running “{$task['name']}”… ";
            $this->invokeHandler($task['handler']);
            echo "✅\n";
        }
    }

    protected function invokeHandler(string $handler): void
    {
        if (strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler, 2);
            if (! class_exists($class) || ! method_exists($class, $method)) {
                throw new \RuntimeException("Invalid task handler: {$handler}");
            }
            (new $class())->{$method}();

        } else {
            // shell command
            passthru($handler, $rc);
            if ($rc !== 0) {
                throw new \RuntimeException("Command failed: {$handler} (exit {$rc})");
            }
        }
    }
}