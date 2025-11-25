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

namespace Lacebox\Sole\Grip;

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Shoelace\SessionInterface;
use Lacebox\Shoelace\SessionDriverInterface;

class SessionManager implements SessionInterface
{
    use SingletonTrait;

    /** @var bool */
    protected $started = false;

    protected $driver;

    /**
     * Private constructor – configure driver from config('session').
     */
    private function __construct()
    {
        $config = function_exists('config') ? config('session') : array();
        if (!is_array($config)) {
            $config = array();
        }

        // Basic cookie settings
        $name     = isset($config['name'])      ? (string)$config['name']      : 'lacephp_session';
        $lifetime = isset($config['lifetime'])  ? (int)$config['lifetime']     : 0;
        $path     = isset($config['path'])      ? (string)$config['path']      : '/';
        $domain   = isset($config['domain'])    ? (string)$config['domain']    : '';
        $secure   = !empty($config['secure']);
        $httponly = array_key_exists('httponly', $config) ? (bool)$config['httponly'] : true;
        $sameSite = isset($config['same_site']) ? (string)$config['same_site'] : 'Lax';

        @session_name($name);

        if (PHP_VERSION_ID >= 70300) {
            @session_set_cookie_params(array(
                'lifetime' => $lifetime,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $sameSite,
            ));
        } else {
            @session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        }

        // Choose storage driver
        $driverName = isset($config['driver']) ? strtolower($config['driver']) : 'file';

        if ($driverName === 'database') {
            $dbConfig = isset($config['database']) && is_array($config['database'])
                ? $config['database']
                : array();
            $this->driver = new Session\DatabaseSessionHandler($dbConfig);
            @session_set_save_handler($this->driver, true);

        } elseif ($driverName === 'cache') {
            $cacheConfig = isset($config['cache']) && is_array($config['cache'])
                ? $config['cache']
                : array();
            $this->driver = new Session\CacheSessionHandler($cacheConfig);
            @session_set_save_handler($this->driver, true);

        } else {
            // FILE driver (default)
            $filePath = null;
            if (isset($config['file']) && is_array($config['file']) && isset($config['file']['path'])) {
                $filePath = (string)$config['file']['path'];
            }

            if ($filePath) {
                if (!is_dir($filePath)) {
                    @mkdir($filePath, 0777, true);
                }
                @session_save_path($filePath);
            }

            // Use PHP's native "files" handler; no custom handler object needed.
            $this->driver = null;
        }
    }

    public function start(): bool
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = array();
            }
            return true;
        }

        if (headers_sent()) {
            return false;
        }

        $ok = @session_start();
        $this->started = $ok;

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = array();
        }

        return $ok;
    }

    public function isStarted(): bool
    {
        return $this->started || session_status() === PHP_SESSION_ACTIVE;
    }

    public function id(): string
    {
        return (string) session_id();
    }

    public function has($key): bool
    {
        $this->start();
        return array_key_exists($key, $_SESSION);
    }

    public function get($key, $default = null)
    {
        $this->start();
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    public function put($key, $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function forget($key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        $this->start();
        return $_SESSION;
    }

    public function flush(): void
    {
        $this->start();
        $_SESSION = array();
    }

    public function regenerate(bool $deleteOld = true): void
    {
        $this->start();
        @session_regenerate_id($deleteOld);
    }

    public function destroy(): void
    {
        if (!$this->isStarted()) {
            return;
        }

        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        @session_destroy();
        $this->started = false;
    }
}