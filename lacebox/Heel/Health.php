<?php
namespace Lacebox\Heel;

use Lacebox\Sole\Cobble\ConnectionManager;

class Health
{
    /**
     * Return overall health with runtime, memory, disk, plus
     * database and cache subsystem statuses.
     *
     * @return array
     */
    public function show(): array
    {
        // 1) Uptime (in seconds, 4 decimal places)
        $uptime = 0;
        if (is_readable('/proc/uptime')) {
            $parts  = explode(' ', trim(file_get_contents('/proc/uptime')));
            $uptime = (float)$parts[0];
        }

        // 2) Memory usage
        $mem     = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);

        // 3) Disk free bytes (root filesystem)
        $diskFree = disk_free_space(__DIR__ . '/../../');

        // Start building the response
        $status = 'ok';
        $info   = [
            'status'         => $status,
            'uptime'         => round($uptime, 4),
            'memoryBytes'    => $mem,
            'memoryPeak'     => $memPeak,
            'diskFreeBytes'  => $diskFree,
            'time'           => date('c'),
        ];

        // 4) Database check
        try {
            $pdo = ConnectionManager::getConnection();
            $pdo->query('SELECT 1');
            $info['database'] = 'ok';
        } catch (\Throwable $e) {
            $info['database'] = 'error: ' . $e->getMessage();
            $status           = 'degraded';
        }

        // 5) Cache directory writable?
        $cfg      = config();
        $cacheDir = dirname(__DIR__, 2)
            . '/'
            . ltrim($cfg['cache']['path'] ?? 'shoebox/cache', '/');
        $writable = is_dir($cacheDir) && is_writable($cacheDir);
        $info['cache'] = $writable ? 'ok' : 'error: not writable';
        if (! $writable) {
            $status = 'degraded';
        }

        // 6) Reflect any degraded status
        $info['status'] = $status;

        return $info;
    }
}