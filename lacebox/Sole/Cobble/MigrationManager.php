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
use PDOException;

class MigrationManager
{
    /** @var PDO|null */
    private static $pdo;

    /**
     * Get or establish PDO connection using environment vars.
     */
    protected static function db(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        self::$pdo = ConnectionManager::getConnection();
        self::ensureTable();
        return self::$pdo;
    }

    /**
     * Create the cobblestones table if it does not exist.
     */
    protected static function ensureTable(): void
    {
        $pdo = self::$pdo ?? ConnectionManager::getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS cobblestones (
                migration VARCHAR(255) PRIMARY KEY,
                ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
            return;
        }

        if ($driver === 'pgsql') {
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS cobblestones (
                migration VARCHAR(255) PRIMARY KEY,
                ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
            return;
        }

        if ($driver === 'sqlite') {
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS cobblestones (
                migration TEXT PRIMARY KEY,
                ran_at TEXT DEFAULT (datetime('now'))
            )
        ");
            return;
        }

        // fallback: attempt generic
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS cobblestones (
            migration VARCHAR(255) PRIMARY KEY,
            ran_at TIMESTAMP
        )
    ");
    }

    public static function listAllMigrations(): array
    {
        $dir = self::migrationsDir();
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/*.php') ?: [];
        sort($files);

        $out = [];
        foreach ($files as $file) {
            $out[] = 'Shoebox\\Migrations\\' . basename($file, '.php');
        }
        return $out;
    }

    public static function pending(): array
    {
        $ran = array_flip(self::getRan());
        $all = self::listAllMigrations();

        $pending = [];
        foreach ($all as $class) {
            if (!isset($ran[$class])) $pending[] = $class;
        }
        return $pending;
    }

    /**
     * Directory where migration files live (relative to project root).
     */
    protected static function migrationsDir(): string
    {
        return dirname(__DIR__, 3) . '/shoebox/migrations';
    }

    /**
     * @return string[] Fully-qualified class names already run
     */
    public static function getRan(): array
    {
        $pdo  = self::db();
        $stmt = $pdo->query('SELECT migration FROM cobblestones');
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Mark a migration as run by inserting its class name into the DB.
     */
    public static function markRan(string $class): void
    {
        $pdo = self::db();
        try {
            $stmt = $pdo->prepare('INSERT INTO cobblestones (migration) VALUES (:migration)');
            $stmt->execute(['migration' => $class]);
        } catch (PDOException $e) {
            // Duplicate entry means already recorded, ignore; otherwise rethrow
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }
    }

    /**
     * Load and run all pending migration classes under shoebox/migrations/.
     */
    public static function runAll(bool $echo = true): array
    {
        $dir = self::migrationsDir();
        if (!is_dir($dir)) {
            if ($echo) echo "No migrations directory found at {$dir}\n";
            return ['ran' => [], 'skipped' => [], 'errors' => ["No migrations directory found at {$dir}"]];
        }

        $ranSet = array_flip(self::getRan());
        $files  = glob($dir . '/*.php') ?: [];
        sort($files);

        $report = ['ran' => [], 'skipped' => [], 'errors' => []];

        foreach ($files as $file) {
            require_once $file;

            $class = 'Shoebox\\Migrations\\' . basename($file, '.php');

            if (!class_exists($class)) {
                $report['skipped'][] = $class;
                continue;
            }

            if (isset($ranSet[$class])) {
                $report['skipped'][] = $class;
                continue;
            }

            $instance = new $class();

            if (!method_exists($instance, 'up')) {
                $report['skipped'][] = $class;
                continue;
            }

            try {
                $instance->up();
                self::markRan($class);
                $report['ran'][] = $class;
                $ranSet[$class] = true;

                if ($echo) echo "Ran migration: {$class}\n";
            } catch (\Throwable $e) {
                $msg = "Failed migration {$class}: " . $e->getMessage();
                $report['errors'][] = $msg;
                if ($echo) echo $msg . "\n";
                // Up to you if you want to stop on first failure:
                // break;
            }
        }

        return $report;
    }
}