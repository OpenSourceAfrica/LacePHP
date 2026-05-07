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
use Lacebox\Shoelace\ShoeResponderInterface;
use Lacebox\Sole\GripResolver;

class ShoeResponder implements ShoeResponderInterface
{
    use SingletonTrait;

    /** @var self|null */
    private static $instance;

    /** @var array */
    protected $headers = [];

    /** @var array */
    protected $data = [];

    /** @var int */
    protected $statusCode = 200;

    /** @var array */
    protected $config = [];

    /** @var GripResolver|null */
    protected $grip;

    protected static $recorder = null;

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function setGripResolver(GripResolver $grip): void
    {
        $this->grip = $grip;
    }

    protected function grip(): GripResolver
    {
        if ($this->grip instanceof GripResolver) {
            return $this->grip;
        }

        $config = $this->config;

        if (empty($config) && function_exists('config')) {
            $config = config();
        }

        $this->grip = GripResolver::make(is_array($config) ? $config : []);
        return $this->grip;
    }

    protected function applySecurityHeaders(): void
    {
        if (! $this->grip()->get('security_headers', false)) {
            return;
        }

        $defaults = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-XSS-Protection' => '1; mode=block',
        ];

        foreach ($defaults as $name => $value) {
            if (! isset($this->headers[$name])) {
                $this->setHeader($name, $value);
            }
        }
    }

    protected function prepareResponse(int $statusCode, array $data = []): void
    {
        $this->statusCode = $statusCode;
        $this->data = $data;

        $this->applySecurityHeaders();

        if (! headers_sent()) {
            http_response_code($statusCode);
        }
    }

    public function html(string $html, int $statusCode = 200): string
    {
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');
        $this->prepareResponse($statusCode, ['html' => $html]);

        return $html;
    }

    public function text(string $text, int $statusCode = 200): string
    {
        $this->setHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->prepareResponse($statusCode, ['text' => $text]);

        return $text;
    }

    public function error(string $message, int $statusCode = 500): string
    {
        return $this->json(
            $this->buildErrorPayload($message, $statusCode),
            $statusCode
        );
    }

    public function json($data, int $status = 200, array $headers = []): string
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        foreach ($headers as $k => $v) {
            $this->setHeader($k, $v);
        }

        $this->prepareResponse($status, is_array($data) ? $data : ['data' => $data]);

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function unauthorized(string $message = 'Unauthorized'): string
    {
        if ($this->wantsHtml()) {
            $html = $this->renderErrorPage('401', [
                'message' => $this->publicErrorMessage($message, 401),
                'statusCode' => 401,
                'title' => 'Unauthorized',
            ]);

            return $this->html($html, 401);
        }

        return $this->json($this->buildErrorPayload($message, 401), 401);
    }

    public function notFound(string $message = 'Not Found'): string
    {
        if ($this->wantsHtml()) {
            $html = $this->renderErrorPage('404', [
                'message' => $this->publicErrorMessage($message, 404),
                'uri' => sole_request()->server('REQUEST_URI') ?? '',
                'statusCode' => 404,
                'title' => 'Not Found',
            ]);

            return $this->html($html, 404);
        }

        return $this->json($this->buildErrorPayload($message, 404), 404);
    }

    public function serverError(string $message = 'Server Error'): string
    {
        if ($this->wantsHtml()) {
            $html = $this->renderErrorPage('500', [
                'message' => $this->publicErrorMessage($message, 500),
                'errorMessage' => $this->detailedErrorsEnabled() ? $message : '',
                'statusCode' => 500,
                'title' => 'Internal Server Error',
            ]);

            return $this->html($html, 500);
        }

        return $this->json($this->buildErrorPayload($message, 500), 500);
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;

        if (! headers_sent()) {
            header($name . ': ' . $value, true);
        }
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    protected function wantsHtml(): bool
    {
        $accept = sole_request()->server('HTTP_ACCEPT') ?? '';
        return strpos($accept, 'text/html') !== false;
    }

    protected function detailedErrorsEnabled(): bool
    {
        $mode = $this->grip()->get('error_detail_mode', 'generic');
        return $mode === 'detailed';
    }

    protected function publicErrorMessage(string $message, int $statusCode): string
    {
        if ($this->detailedErrorsEnabled()) {
            return $message;
        }

        switch ($statusCode) {
            case 401:
                return 'Unauthorized';

            case 404:
                return 'Not Found';

            case 500:
            default:
                return 'Internal Server Error';
        }
    }

    protected function buildErrorPayload(string $message, int $statusCode, array $extra = []): array
    {
        $payload = [
            'error' => $this->publicErrorMessage($message, $statusCode),
        ];

        if ($this->detailedErrorsEnabled()) {
            $payload['detail'] = $message;
        }

        if (! empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        return $payload;
    }

    /**
     * Convert response state to array for recording.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status' => $this->statusCode,
            'headers' => $this->headers,
            'data' => $this->data,
        ];
    }

    /**
     * Set response payload data.
     *
     * @param array $data
     * @return $this
     */
    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Load and render an error template (401, 404, 500, etc.)
     */
    protected function renderErrorPage(string $code, array $vars = []): string
    {
        $cfg = function_exists('config') ? (config('errors') ?? []) : [];
        $tpls = $cfg['templates'] ?? [];

        $projectRoot = dirname(__DIR__, 2);

        if (isset($tpls[$code])) {
            $candidate = $tpls[$code];

            if (! preg_match('#^(?:/|[A-Za-z]:\\\\)#', $candidate)) {
                $path = $projectRoot . '/' . ltrim($candidate, '/');
            } else {
                $path = $candidate;
            }
        } else {
            $path = $projectRoot . '/shoebox/views/errors/' . $code . '.html.php';
        }

        if (! file_exists($path)) {
            return $this->fallbackErrorHtml($code, $vars);
        }

        extract($vars, EXTR_SKIP);

        ob_start();
        include $path;
        return ob_get_clean();
    }

    protected function fallbackErrorHtml(string $code, array $vars = []): string
    {
        $title = $vars['title'] ?? ('Error ' . $code);
        $message = $vars['message'] ?? '';

        return '<!DOCTYPE html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
            . '</head>'
            . '<body>'
            . '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</body>'
            . '</html>';
    }
}