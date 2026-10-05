<?php

namespace App\Core;

class Request {
    private string $method;
    private string $path;
    private array $queryParams;
    private array $body;
    private ?array $jsonBody = null;

    public function __construct() {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $this->path = rawurldecode($path) ?: '/';
        $this->queryParams = $_GET;
        $this->body = $_POST;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                $this->jsonBody = $decoded;
            }
        }
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed {
        return $this->queryParams[$key] ?? $default;
    }

    public function allQuery(): array {
        return $this->queryParams;
    }

    public function input(string $key, mixed $default = null): mixed {
        if ($this->jsonBody !== null) {
            return $this->jsonBody[$key] ?? $default;
        }
        return $this->body[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function all(): array {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }
        return array_merge($this->queryParams, $this->body);
    }

    public function isJson(): bool {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
    }

    public function header(string $key, mixed $default = null): mixed {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$headerKey] ?? $default;
    }
}
