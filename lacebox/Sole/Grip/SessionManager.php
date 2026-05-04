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

class SessionManager implements SessionInterface
{
    use SingletonTrait;

    /** @var bool */
    protected $started = false;

    /** @var mixed|null */
    protected $driver;

    /** @var int default TTL in seconds (0 = none) */
    protected $defaultTtl = 0;

    /** @var string session header for token-based sessions (optional) */
    protected $headerName = '';

    // Internal wrapper keys (avoid collisions with user keys)
    private const TTL_VALUE_KEY   = '__lace_value';
    private const TTL_EXPIRES_KEY = '__lace_expires_at';

    /** @var bool */
    protected $isFileDriver = false;

    /** @var int seconds between sweeps (throttle) */
    protected $sweepInterval = 300;

    /** @var int limit deletions per sweep to avoid long requests */
    protected $sweepMaxFiles = 500;

    /** @var bool */
    protected $sweepEnabled = true;

    private function __construct()
    {
        $config = function_exists('config') ? config('session') : array();
        if (!is_array($config)) $config = array();

        // Default TTL (your middleware reads config('session.ttl') – align with that)
        $this->defaultTtl = isset($config['ttl']) ? (int)$config['ttl'] : 0;

        // If you want token-based session-id via header (optional but useful for APIs)
        $this->headerName = isset($config['header']) ? (string)$config['header'] : '';

        // --- IMPORTANT: wire PHP's session garbage collector to TTL ---
        // If ttl is set, tell PHP GC how long session data should live.
        if ($this->defaultTtl > 0) {
            @ini_set('session.gc_maxlifetime', (string)$this->defaultTtl);

            // Encourage cleanup (shared hosts often have low GC frequency)
            // 1% chance per request:
            @ini_set('session.gc_probability', '1');
            @ini_set('session.gc_divisor', '100');
        }

        // Sweeper config
        $this->sweepEnabled  = array_key_exists('sweep_enabled', $config) ? (bool)$config['sweep_enabled'] : true;
        $this->sweepInterval = isset($config['sweep_interval']) ? (int)$config['sweep_interval'] : 300;
        $this->sweepMaxFiles = isset($config['sweep_max_files']) ? (int)$config['sweep_max_files'] : 500;

        // Basic cookie settings
        $name     = isset($config['name']) ? (string)$config['name'] : 'lacephp_session';
        $lifetime = isset($config['lifetime']) ? (int)$config['lifetime'] : 0;
        $path     = isset($config['path']) ? (string)$config['path'] : '/';
        $domain   = isset($config['domain']) ? (string)$config['domain'] : '';
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
            $this->isFileDriver = false;

            $dbConfig = (isset($config['database']) && is_array($config['database'])) ? $config['database'] : array();
            $dbConfig['ttl'] = isset($dbConfig['ttl']) ? (int)$dbConfig['ttl'] : $this->defaultTtl;

            $this->driver = new Session\DatabaseSessionHandler($dbConfig);
            @session_set_save_handler($this->driver, true);

        } elseif ($driverName === 'cache') {
            $this->isFileDriver = false;

            $cacheConfig = (isset($config['cache']) && is_array($config['cache'])) ? $config['cache'] : array();
            $cacheConfig['ttl'] = isset($cacheConfig['ttl']) ? (int)$cacheConfig['ttl'] : $this->defaultTtl;

            $this->driver = new Session\CacheSessionHandler($cacheConfig);
            @session_set_save_handler($this->driver, true);

        } else {
            // FILE driver (default)
            $this->isFileDriver = true;

            // Prefer configured path; otherwise choose a safe writable path
            $filePath = null;

            if (isset($config['file']) && is_array($config['file']) && isset($config['file']['path'])) {
                $filePath = (string)$config['file']['path'];
            }

            // If not set, fall back to sys temp (works on shared hosting)
            if (!$filePath) {
                $filePath = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lacephp_sessions';
            }

            if (!is_dir($filePath)) {
                @mkdir($filePath, 0777, true);
            }

            // If still not writable, fallback again (last resort)
            if (!is_writable($filePath)) {
                $filePath = (string)sys_get_temp_dir();
            }

            @session_save_path($filePath);

            // Use PHP's native "files" handler
            $this->driver = null;
        }
    }

    public function start(): bool
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            if (!isset($_SESSION) || !is_array($_SESSION)) $_SESSION = array();
            $this->purgeExpiredKeys();
            return true;
        }

        if (headers_sent()) {
            return false;
        }

        //Centralised shared-host safety
        $this->ensureSessionEnvironment();

        $this->maybeSweepFileSessions();

        // Optional: token-based sessions for APIs
        // If header exists, use it as session_id BEFORE session_start
        if ($this->headerName) {
            $token = null;
            if (function_exists('sole_request')) {
                $token = sole_request()->header($this->headerName);
            } else {
                $hdrKey = 'HTTP_' . strtoupper(str_replace('-', '_', $this->headerName));
                $token = isset($_SERVER[$hdrKey]) ? $_SERVER[$hdrKey] : null;
            }

            if ($token && is_string($token) && session_status() === PHP_SESSION_NONE) {
                @session_id($token);
            }
        }

        $ok = @session_start();
        $this->started = $ok;

        if (!isset($_SESSION) || !is_array($_SESSION)) $_SESSION = array();

        // Prevent bloat of TTL-wrapped keys
        $this->purgeExpiredKeys();

        return $ok;
    }

    public function isStarted(): bool
    {
        return $this->started || session_status() === PHP_SESSION_ACTIVE;
    }

    public function id(): string
    {
        return (string)session_id();
    }

    public function has($key): bool
    {
        $this->start();
        if (!array_key_exists($key, $_SESSION)) return false;

        // respect TTL
        $v = $_SESSION[$key];
        if ($this->isTtlWrapped($v) && $this->isExpiredWrapped($v)) {
            unset($_SESSION[$key]);
            return false;
        }
        return true;
    }

    public function get($key, $default = null)
    {
        $this->start();

        if (!array_key_exists($key, $_SESSION)) {
            return $default;
        }

        $val = $_SESSION[$key];

        if ($this->isTtlWrapped($val)) {
            if ($this->isExpiredWrapped($val)) {
                unset($_SESSION[$key]);
                return $default;
            }
            return $val[self::TTL_VALUE_KEY];
        }

        return $val;
    }

    /**
     * Put a value into the session.
     * If $ttl is null, uses config('session.ttl') if set. (0 or <=0 means no TTL.)
     */
    public function put($key, $value, $ttl = null): void
    {
        $this->start();

        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        }

        $ttl = (int)$ttl;

        if ($ttl <= 0) {
            $_SESSION[$key] = $value;
            return;
        }

        $_SESSION[$key] = array(
            self::TTL_VALUE_KEY   => $value,
            self::TTL_EXPIRES_KEY => time() + $ttl,
        );
    }

    public function forget($key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        $this->start();
        $this->purgeExpiredKeys();

        $out = array();
        foreach ($_SESSION as $k => $v) {
            if ($this->isTtlWrapped($v)) {
                $out[$k] = $v[self::TTL_VALUE_KEY];
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
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
        if (!$this->isStarted()) return;

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

    private function purgeExpiredKeys(): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) return;

        $now = time();
        foreach ($_SESSION as $k => $v) {
            if (!$this->isTtlWrapped($v)) continue;

            $exp = (int)$v[self::TTL_EXPIRES_KEY];
            if ($exp > 0 && $now >= $exp) {
                unset($_SESSION[$k]);
            }
        }
    }

    private function isTtlWrapped($v): bool
    {
        return is_array($v)
            && array_key_exists(self::TTL_VALUE_KEY, $v)
            && array_key_exists(self::TTL_EXPIRES_KEY, $v);
    }

    private function isExpiredWrapped(array $v): bool
    {
        $exp = (int)$v[self::TTL_EXPIRES_KEY];
        return $exp > 0 && time() >= $exp;
    }

    private function ensureSessionEnvironment(): void
    {
        // If session is already active, nothing to do
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Only relevant for file-based sessions (native handler)
        // If you use DB/Cache handlers, PHP won't read/write to session_save_path().
        $config = function_exists('config') ? config('session') : array();
        if (!is_array($config)) $config = array();

        $driverName = isset($config['driver']) ? strtolower($config['driver']) : 'file';

        if ($driverName !== 'file') {
            return;
        }

        // If LacePHP already configured a path in __construct(), this is just a safety net.
        $path = ini_get('session.save_path');

        // If cPanel path is not readable/writable for this account, session_start fails.
        // We switch to a known-writable directory.
        $badCpanelPath = (is_string($path) && strpos($path, '/var/cpanel/php/sessions') !== false);

        $needsFix = $badCpanelPath || empty($path) || !is_dir($path) || !is_writable($path);

        if ($needsFix) {
            $filePath = null;

            if (isset($config['file']['path'])) {
                $filePath = (string)$config['file']['path'];
            }

            if (!$filePath) {
                $filePath = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lacephp_sessions';
            }

            if (!is_dir($filePath)) {
                @mkdir($filePath, 0777, true);
            }

            if (is_dir($filePath) && is_writable($filePath)) {
                @session_save_path($filePath);
            } else {
                // last resort: sys temp dir directly
                @session_save_path((string)sys_get_temp_dir());
            }
        }
    }

    private function maybeSweepFileSessions(): void
    {
        if (!$this->sweepEnabled) return;
        if (!$this->isFileDriver) return;

        // Determine max lifetime
        $maxLifetime = $this->defaultTtl > 0
            ? $this->defaultTtl
            : (int)ini_get('session.gc_maxlifetime');

        if ($maxLifetime <= 0) return;

        $dir = $this->getSessionSaveDir();
        if (!$dir || !is_dir($dir) || !is_writable($dir)) return;

        // Throttle: run at most once every $sweepInterval seconds per directory
        $lockFile = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.lacephp_session_sweep';

        // If lock exists and is fresh, skip
        if (is_file($lockFile)) {
            $age = time() - (int)@filemtime($lockFile);
            if ($age >= 0 && $age < $this->sweepInterval) {
                return;
            }
        }

        // Attempt to lock (avoid concurrent sweeps)
        $fp = @fopen($lockFile, 'c+');
        if (!$fp) return;

        try {
            if (!@flock($fp, LOCK_EX | LOCK_NB)) {
                return;
            }

            // Refresh lock timestamp
            @ftruncate($fp, 0);
            @fwrite($fp, (string)time());
            @fflush($fp);

            $this->sweepExpiredSessionFiles($dir, $maxLifetime);

        } finally {
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @touch($lockFile);
        }
    }

    private function sweepExpiredSessionFiles(string $dir, int $maxLifetime): void
    {
        $cutoff = time() - $maxLifetime;
        $deleted = 0;

        $dh = @opendir($dir);
        if (!$dh) return;

        while (($file = readdir($dh)) !== false) {
            if ($deleted >= $this->sweepMaxFiles) break;

            // Only delete PHP session files
            if (strpos($file, 'sess_') !== 0) continue;

            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (!is_file($path)) continue;

            $mtime = (int)@filemtime($path);
            if ($mtime > 0 && $mtime < $cutoff) {
                @unlink($path);
                $deleted++;
            }
        }

        @closedir($dh);
    }

    /**
     * session.save_path can be:
     * - "/path"
     * - "5;/path" (depth;path)
     * - multiple paths separated by ';' in some setups
     */
    private function getSessionSaveDir(): ?string
    {
        $savePath = (string)ini_get('session.save_path');
        if ($savePath === '') return null;

        // If format "N;/path"
        if (preg_match('/^\d+;(.*)$/', $savePath, $m)) {
            $savePath = $m[1];
        }

        // If multiple paths separated by ':', ';' etc. (rare but possible)
        // We'll take the first real directory-like chunk.
        $candidates = preg_split('/[:;]/', $savePath) ?: [];
        foreach ($candidates as $c) {
            $c = trim($c);
            if ($c !== '' && is_dir($c)) return $c;
        }

        // Otherwise treat as direct path
        return is_dir($savePath) ? $savePath : null;
    }

}