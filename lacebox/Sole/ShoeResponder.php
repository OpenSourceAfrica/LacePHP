<?php

namespace Lacebox\Sole;

use Lacebox\Shoelace\ShoeResponderInterface;

class ShoeResponder implements ShoeResponderInterface
{
    /** @var self|null */
    private static $instance;

    /** @var array */
    protected $headers = [];

    /** private so you must go through getInstance() */
    private function __construct() {}

    /** always use this */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function html(string $html, int $statusCode = 200): string
    {
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');
        if (! headers_sent()) {
            http_response_code($statusCode);
        }
        return $html;
    }

    public function text(string $text, int $statusCode = 200): string
    {
        $this->setHeader('Content-Type', 'text/plain; charset=utf-8');
        if (! headers_sent()) {
            http_response_code($statusCode);
        }
        return $text;
    }

    public function error(string $message, int $statusCode = 500): string
    {
        $this->setHeader('Content-Type', 'application/json');
        if (! headers_sent()) {
            http_response_code($statusCode);
        }
        return json_encode(['error' => $message], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    public function json($data, int $status = 200, array $headers = []): string
    {
        $this->setHeader('Content-Type', 'application/json');
        if (! headers_sent()) {
            http_response_code($status);
        }
        foreach ($headers as $k => $v) {
            header("$k: $v", true, $status);
        }
        return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    public function unauthorized(string $message = 'Unauthorized'): string
    {
        if ($this->wantsHtml()) {
            $html = $this->renderErrorPage('errors/401', [
                'message' => $message
            ]);
            return $this->html($html, 401);
        }
        return $this->json(['error' => $message], 401);
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
        if (! headers_sent()) {
            header("$name: $value", true);
        }
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    protected function wantsHtml(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'text/html') !== false;
    }

    public function notFound(string $message = 'Not Found'): string
    {
        if ($this->wantsHtml()) {
            $html = $this->renderErrorPage('404', [
                'message' => $message,
                'uri'     => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            return $this->html($html, 404);
        }
        return $this->json(['error'=>$message], 404);
    }

    public function serverError(string $message = 'Server Error'): string
    {
        if ($this->wantsHtml()) {
            $html = $this->renderErrorPage('500', [
                // only show details if errors.show_debug = true
                'errorMessage' => config('errors.show_debug')
                    ? $message
                    : '',
            ]);
            return $this->html($html, 500);
        }
        return $this->json(['error'=>$message], 500);
    }

    /**
     * Load and render an error template (404, 500, etc.).
     */
    protected function renderErrorPage(string $code, array $vars = []): string
    {
        $cfg  = config('errors') ?? [];
        $tpls = $cfg['templates'] ?? [];

        // Project root (where lace.json lives)
        $projectRoot = dirname(__DIR__, 2);

        if (isset($tpls[$code])) {
            $candidate = $tpls[$code];

            // If not absolute, prefix project root
            if (! preg_match('#^(?:/|[A-Za-z]:\\\\)#', $candidate)) {
                $path = $projectRoot . '/' . ltrim($candidate, '/');
            } else {
                $path = $candidate;
            }
        } else {
            // fallback default under shoebox/views/errors/
            $path = $projectRoot . '/shoebox/views/errors/' . $code . '.html';
        }

        if (! file_exists($path)) {
            // fallback minimal
            return $vars[$code === '500' ? 'errorMessage' : 'message'] ?? '';
        }

        // load the template
        extract($vars, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}