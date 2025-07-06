<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
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
        try {
            self::$pdo = ConnectionManager::getConnection();
            self::ensureTable();
            return self::$pdo;
        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * Create the cobblestones table if it does not exist.
     */
    protected static function ensureTable(): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS cobblestones (
    migration VARCHAR(255) PRIMARY KEY,
    ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
SQL;
        self::$pdo->exec($sql);
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
    public static function runAll(): void
    {
        $dir = self::migrationsDir();
        if (!is_dir($dir)) {
            echo "No migrations directory found at {$dir}\n";
            return;
        }

        $ran   = self::getRan();
        $files = glob($dir . '/*.php') ?: [];

        foreach ($files as $file) {
            require_once $file;
            $class = 'Shoebox\\Migrations\\' . basename($file, '.php');

            if (!class_exists($class) || in_array($class, $ran, true)) {
                continue;
            }

            $instance = new $class();
            if (!method_exists($instance, 'up')) {
                continue;
            }

            $instance->up();
            self::markRan($class);
            echo "Ran migration: {$class}\n";
        }
    }
}