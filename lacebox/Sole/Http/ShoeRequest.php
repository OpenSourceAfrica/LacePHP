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

namespace Lacebox\Sole\Http;

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Sole\UriResolver;
use RuntimeException;

/**
 * HTTP Request wrapper with method- and input-sanitization,
 * immutable single instance (Singleton) for global access.
 */
class ShoeRequest
{
    use SingletonTrait;

    /** Sanitized GET parameters */
    private $get = [];
    /** Sanitized POST parameters */
    private $post = [];
    /** Sanitized JSON body */
    private $json = [];
    /** Sanitized SERVER params */
    private $server = [];
    /** Sanitized uploaded files */
    private $files = [];

    protected static $recorder = null;

    /**
     * Private to enforce singleton
     */
    private function __construct()
    {
        // Initialize session for CSRF
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->server = $this->sanitizeServer($_SERVER);
        $this->get    = $this->sanitizeArray($_GET);
        $this->post   = $this->sanitizeArray($_POST);

        $body = file_get_contents('php://input');
        $decoded = @json_decode($body, true);
        if (is_array($decoded)) {
            $this->json = $this->sanitizeArray($decoded);
        }

        $this->files = $this->sanitizeFiles($_FILES);

        // Ensure CSRF token exists
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Retrieve the singleton instance
     */
    public static function grab(): self
    {
        return self::getInstance();
    }

    /**
     * Get a sanitized input value by key, with optional default.
     * JSON body values override POST, which override GET.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        if (array_key_exists($key, $this->json)) {
            return $this->json[$key];
        }
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        if (array_key_exists($key, $this->get)) {
            return $this->get[$key];
        }
        return $default;
    }

    /**
     * All sanitized input parameters merged: GET, POST, JSON
     * @return array
     */
    public function all(): array
    {
        return array_replace_recursive($this->get, $this->post, $this->json);
    }

    /**
     * Return only the given keys from all inputs.
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Exclude the given keys from all inputs.
     * @param array $keys
     * @return array
     */
    public function except(array $keys): array
    {
        $all = $this->all();
        return array_diff_key($all, array_flip($keys));
    }

    /**
     * Retrieve a header value (sanitized), or default.
     */
    public function header(string $name, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? $default;
    }

    public function server(string $key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * HTTP request method
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Request URI (path + query)
     */
    public function uri(): string
    {
        return UriResolver::resolve();
    }

    /**
     * Client IP (trusted header or REMOTE_ADDR)
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    /**
     * Uploaded files (sanitized)
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Create an array representation of the request.
     */
    public function toArray(): array
    {
        return [
            'method'  => $this->method(),
            'uri'     => $this->server('REQUEST_URI') ?? '/',
            'headers' => getallheaders(),
            'body'    => file_get_contents('php://input'),
        ];
    }

    // --------- CSRF Helpers ---------

    /**
     * Get the current CSRF token
     */
    public function csrfToken(): string
    {
        return $_SESSION['_csrf_token'];
    }

    /**
     * Get an HTML input field for CSRF
     */
    public function csrfField(): string
    {
        $token = $this->csrfToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF token on state-changing requests
     * @throws RuntimeException
     */
    public function validateCsrf(): void
    {
        $method = strtoupper($this->method());
        if (in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
            $token = $this->input('_token', '') ?: $this->header('X-CSRF-TOKEN', '');
            if (!hash_equals($this->csrfToken(), $token)) {
                throw new RuntimeException('Invalid CSRF token');
            }
        }
    }

    // --------- Sanitization helpers ---------

    /**
     * Recursively sanitize an array of data (keys & values).
     */
    private function sanitizeArray(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            $cleanKey = $this->sanitizeKey($key);
            if (is_array($value)) {
                $cleanVal = $this->sanitizeArray($value);
            } else {
                $cleanVal = $this->sanitizeValue($value);
            }
            $clean[$cleanKey] = $cleanVal;
        }
        return $clean;
    }

    /**
     * Sanitize superglobal keys to alphanumeric+underscore only.
     */
    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $key) ?: $key;
    }

    /**
     * Sanitize a single string value: trim, strip nulls, strip tags.
     */
    private function sanitizeValue(string $value): string
    {
        $v = trim($value);
        $v = str_replace("\0", '', $v);
        return strip_tags($v);
    }

    /**
     * Sanitize the $_SERVER superglobal to only allow safe keys/values.
     */
    private function sanitizeServer(array $server): array
    {
        $clean = [];
        foreach ($server as $key => $value) {
            if (preg_match('/^(REQUEST_METHOD|REQUEST_URI|HTTP_[A-Z0-9_]+|REMOTE_ADDR|HTTP_X_FORWARDED_FOR)$/', $key)) {
                $clean[$key] = is_array($value) ? $value : $this->sanitizeValue((string)$value);
            }
        }
        return $clean;
    }

    /**
     * Sanitize uploaded files array
     */
    private function sanitizeFiles(array $files): array
    {
        $clean = [];
        foreach ($files as $field => $info) {
            if (!isset($info['name'])) continue;
            if (is_array($info['name'])) {
                $count = count($info['name']);
                for ($i = 0; $i < $count; $i++) {
                    $clean[$field][$i] = [
                        'name'     => basename($info['name'][$i]),
                        'type'     => $info['type'][$i] ?? '',
                        'tmp_name' => $info['tmp_name'][$i] ?? '',
                        'error'    => (int)($info['error'][$i] ?? 0),
                        'size'     => (int)($info['size'][$i] ?? 0),
                    ];
                }
            } else {
                $clean[$field] = [
                    'name'     => basename($info['name']),
                    'type'     => $info['type'] ?? '',
                    'tmp_name' => $info['tmp_name'] ?? '',
                    'error'    => (int)($info['error'] ?? 0),
                    'size'     => (int)($info['size'] ?? 0),
                ];
            }
        }
        return $clean;
    }
}