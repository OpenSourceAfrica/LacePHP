<?php
namespace Lacebox\Sole;

use Lacebox\Shoelace\EyeletDispatcherInterface;

class EyeletDispatcher implements EyeletDispatcherInterface
{
    /** @var EyeletDispatcher|null */
    private static $instance = null;

    /** @var array<string, callable[]> */
    protected $listeners = [];

    /** private so no one can `new` it directly */
    private function __construct() {}

    /** get or create the single instance */
    public static function getInstance(): EyeletDispatcher
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function listen(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(string $eventName, $payload = null): void
    {
        $toNotify = $this->listeners[$eventName] ?? [];
        $wildcard = $this->listeners['*'] ?? [];

        foreach (array_merge($toNotify, $wildcard) as $listener) {
            try {
                $listener($payload);
            } catch (\Throwable $e) {
                log_error(
                    '500',
                    "Eyelet listener for {$eventName} failed: " . $e->getMessage()
                );
            }
        }
    }
}
