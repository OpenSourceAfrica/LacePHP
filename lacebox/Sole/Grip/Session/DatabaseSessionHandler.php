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

namespace Lacebox\Sole\Grip\Session;

use Lacebox\Sole\Cobble\ConnectionManager;
use PDO;

class DatabaseSessionHandler
{
    /** @var string */
    protected $table;

    /** @var int */
    protected $lifetime;

    public function __construct(array $config = array())
    {
        $this->table    = isset($config['table']) ? (string)$config['table'] : 'sessions';
        $this->lifetime = isset($config['lifetime']) ? (int)$config['lifetime'] : (int) ini_get('session.gc_maxlifetime');
    }

    public function open($savePath, $sessionName)
    {
        // Nothing special to do here; connection is managed by ConnectionManager.
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($session_id)
    {
        try {
            $pdo = ConnectionManager::getConnection();
            $sql = "SELECT payload FROM `{$this->table}` WHERE id = :id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(':id' => $session_id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row['payload'])) {
                return (string)$row['payload'];
            }
        } catch (\Throwable $e) {
            // swallow and fall back to empty session
        }

        return '';
    }

    public function write($session_id, $session_data)
    {
        $time = time();

        try {
            $pdo    = ConnectionManager::getConnection();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'sqlite') {
                $sql = "INSERT OR REPLACE INTO \"{$this->table}\" (id, payload, last_activity)
                        VALUES (:id, :payload, :time)";
            } elseif ($driver === 'mysql') {
                $sql = "INSERT INTO `{$this->table}` (id, payload, last_activity)
                        VALUES (:id, :payload, :time)
                        ON DUPLICATE KEY UPDATE payload = VALUES(payload), last_activity = VALUES(last_activity)";
            } else {
                // Fallback: delete then insert
                $pdo->prepare("DELETE FROM `{$this->table}` WHERE id = :id")
                    ->execute(array(':id' => $session_id));

                $sql = "INSERT INTO `{$this->table}` (id, payload, last_activity)
                        VALUES (:id, :payload, :time)";
            }

            $stmt = $pdo->prepare($sql);
            return $stmt->execute(array(
                ':id'      => $session_id,
                ':payload' => $session_data,
                ':time'    => $time,
            ));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function destroy($session_id)
    {
        try {
            $pdo = ConnectionManager::getConnection();
            $sql = "DELETE FROM `{$this->table}` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(':id' => $session_id));
        } catch (\Throwable $e) {
            // ignore
        }

        return true;
    }

    public function gc($max_lifetime)
    {
        try {
            $pdo   = ConnectionManager::getConnection();
            $limit = time() - (int)$max_lifetime;
            $sql   = "DELETE FROM `{$this->table}` WHERE last_activity < :limit";
            $stmt  = $pdo->prepare($sql);
            $stmt->execute(array(':limit' => $limit));
        } catch (\Throwable $e) {
            // ignore
        }

        return true;
    }
}