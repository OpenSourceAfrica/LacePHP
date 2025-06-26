<?php
namespace Lacebox\Sole\Http;

class Request
{
    /**
     * @var Request|null
     */
    private static $instance = null;

    private $get;
    private $post;
    private $server;
    private $json;

    private function __construct()
    {
        $this->get    = $_GET;
        $this->post   = $_POST;
        $this->server = $_SERVER;
        $body        = file_get_contents('php://input');
        $this->json   = @json_decode($body, true) ?: [];
    }

    /**
     * @return Request
     */
    public static function grab()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function input(string $key, $default = null)
    {
        // JSON body overrides POST
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

    public function all(): array
    {
        // merge GET, POST, JSON body
        return array_replace_recursive($this->get, $this->post, $this->json);
    }

    public function header(string $name, $default = null)
    {
        $h = strtoupper(str_replace('-', '_', $name));
        return $this->server["HTTP_{$h}"]
            ?? $this->server[$h]
            ?? $default;
    }

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function uri(): string
    {
        return \Lacebox\Sole\UriResolver::resolve();
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR']
            ?? $this->server['HTTP_X_FORWARDED_FOR']
            ?? '0.0.0.0';
    }
}
