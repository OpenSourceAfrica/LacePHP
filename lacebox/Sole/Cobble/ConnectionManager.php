<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.lacephp.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Sole\Cobble;

use PDO;

class ConnectionManager
{
    /** @var PDO|null */
    protected static $pdo   = null;
    /** @var string|null */
    protected static $lastDsn = null;
    /** @var string|null */
    protected static $lastUser = null;
    /** @var string|null */
    protected static $lastPass = null;

    /**
     * Return the shared PDO, creating it if needed.
     */
    public static function getConnection(): PDO
    {
        try {

            $cfg    = config()['database'] ?? [];
            $driver = $cfg['driver'] ?? 'sqlite';

            // build the DSN (and user/pass) just once
            switch ($driver) {
                case 'mysql':
                    $host = $cfg['mysql']['host'] ?? '127.0.0.1';
                    $port = $cfg['mysql']['port'] ?? null;
                    $name = $cfg['mysql']['database'] ?? '';
                    $dsn  = "mysql:host={$host}"
                        . ($port ? ";port={$port}" : '')
                        . ";dbname={$name};charset=utf8mb4";
                    $user = $cfg['mysql']['username'] ?? null;
                    $pass = $cfg['mysql']['password'] ?? null;
                    break;

                case 'pgsql':
                    $host = $cfg['pgsql']['host'] ?? '127.0.0.1';
                    $port = $cfg['pgsql']['port'] ?? null;
                    $name = $cfg['pgsql']['database'] ?? '';
                    $dsn  = "pgsql:host={$host}"
                        . ($port ? ";port={$port}" : '')
                        . ";dbname={$name}";
                    $user = $cfg['pgsql']['username'] ?? ($cfg['mysql']['username'] ?? null);
                    $pass = $cfg['pgsql']['password'] ?? ($cfg['mysql']['password'] ?? null);
                    break;

                case 'sqlite':
                default:
                    // get whatever the user configured (may be absolute or relative)
                    $rawPath = $cfg['sqlite']['database_file']
                        ?? dirname(__DIR__, 3) . '/database.sqlite';

                    // determine project root (three levels up from this file)
                    $projectRoot = dirname(__DIR__, 3);

                    // if $rawPath is not absolute, make it relative to project root
                    $isAbsolute = (DIRECTORY_SEPARATOR === '\\')
                        ? preg_match('#^[A-Za-z]:\\\\#', $rawPath) || strpos($rawPath, '\\\\') === 0
                        : strpos($rawPath, '/') === 0;

                    if (! $isAbsolute) {
                        // strip any leading slashes just in case
                        $rawPath = ltrim($rawPath, '/\\');
                        $rawPath = $projectRoot . DIRECTORY_SEPARATOR . $rawPath;
                    }

                    $dsn  = "sqlite:{$rawPath}";
                    $user = null;
                    $pass = null;

                    break;
            }

            // if we already have a connection *and* it's for the same DSN+credentials, reuse it
            if (self::$pdo
                && self::$lastDsn  === $dsn
                && self::$lastUser === $user
                && self::$lastPass === $pass
            ) {
                return self::$pdo;
            }

            // otherwise build a brand-new one
            $options = ($cfg['mysql']['options'] ?? []) + [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];

            self::$pdo       = new PDO($dsn, $user, $pass, $options);
            self::$lastDsn   = $dsn;
            self::$lastUser  = $user;
            self::$lastPass  = $pass;

            // Only set time_zone for drivers that support it
            $tz = config()['boot']['timezone'] ?? 'UTC';
            $date    = new \DateTime('now', new \DateTimeZone($tz));

            // format as "+HH:MM" or "-HH:MM"
            $offset  = $date->format('P');

            if ($driver === 'mysql') {
                // use offset instead of zone name
                self::$pdo->exec("SET time_zone = '{$offset}'");
            } elseif ($driver === 'pgsql') {
                // Postgres will accept the named zone
                self::$pdo->exec("SET TIME ZONE '{$tz}'");
            }

            return self::$pdo;

        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public static function reset(): void
    {
        self::$pdo       = null;
        self::$lastDsn   = null;
        self::$lastUser  = null;
        self::$lastPass  = null;
    }
}