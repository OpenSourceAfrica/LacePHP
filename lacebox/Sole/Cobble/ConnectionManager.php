<?php
namespace Lacebox\Sole\Cobble;

use PDO;
use DateTimeZone;

class ConnectionManager
{
    /** @var PDO|null */
    protected static $pdo;

    /**
     * Return the shared PDO, creating it if needed.
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $cfg    = config()['database'] ?? [];
        $driver = $cfg['driver']   ?? 'sqlite';
        $host   = $cfg['mysql']['host']     ?? '127.0.0.1';
        $port   = $cfg['mysql']['port']     ?? null;
        $name   = $cfg['mysql']['database'] ?? '';
        $user   = $cfg['mysql']['username'] ?? null;
        $pass   = $cfg['mysql']['password'] ?? null;
        $opts   = $cfg['mysql']['options']  ?? [];

        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host={$host}"
                    . ($port ? ";port={$port}" : '')
                    . ";dbname={$name};charset=utf8mb4";
                break;

            case 'pgsql':
                $hostPg = $cfg['pgsql']['host']     ?? '127.0.0.1';
                $portPg = $cfg['pgsql']['port']     ?? null;
                $namePg = $cfg['pgsql']['database'] ?? '';
                $dsn    = "pgsql:host={$hostPg}"
                    . ($portPg ? ";port={$portPg}" : '')
                    . ";dbname={$namePg}";
                // override user/pass from pgsql block if needed:
                $user = $cfg['pgsql']['username'] ?? $user;
                $pass = $cfg['pgsql']['password'] ?? $pass;
                break;

            case 'sqlite':
            default:
                $path = $cfg['sqlite']['database_file']
                    ?? dirname(__DIR__,3) . '/database.sqlite';
                $dsn  = "sqlite:{$path}";
                // sqlite has no user/pass
                $user = null;
                $pass = null;
                break;
        }

        $options = $opts + [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

        self::$pdo = new PDO($dsn, $user, $pass, $options);

        // Only set time_zone for drivers that support it
        $tz = config()['boot']['timezone'] ?? 'UTC';
        $zone = (new DateTimeZone($tz))->getName();

        if ($driver === 'mysql') {
            self::$pdo->exec("SET time_zone = '{$zone}'");
        } elseif ($driver === 'pgsql') {
            self::$pdo->exec("SET TIME ZONE '{$zone}'");
        }

        return self::$pdo;
    }
}