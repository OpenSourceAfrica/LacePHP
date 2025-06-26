<?php

namespace Lacebox\Shoelace;

interface ShoeResponderInterface
{
    public function json($data, int $statusCode = 200): string;

    public function html(string $html, int $statusCode = 200): string;

    public function text(string $text, int $statusCode = 200): string;

    public function error(string $message, int $statusCode = 500): string;

    public function unauthorized(string $message = 'Unauthorized'): string;

    public function notFound(string $message = 'Not Found'): string;

    public function serverError(string $message = 'Internal Server Error'): string;

    public function setHeader(string $name, string $value): void;

    public function getHeaders(): array;
}
