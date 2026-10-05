<?php

namespace App\Core;

class Response {
    public static function json(mixed $data, int $status = 200, array $headers = []): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function view(string $viewPath, array $data = [], int $status = 200): void {
        http_response_code($status);
        extract($data);
        $fullPath = dirname(__DIR__, 2) . '/views/' . ltrim($viewPath, '/') . '.php';
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "View not found: " . htmlspecialchars($viewPath);
            exit;
        }
        require $fullPath;
        exit;
    }

    public static function redirect(string $url, int $status = 302): void {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    public static function error(string $message, int $status = 400, array $extra = []): void {
        self::json(array_merge(['success' => false, 'error' => $message], $extra), $status);
    }

    public static function success(array $data = [], string $message = 'Success'): void {
        self::json(array_merge(['success' => true, 'message' => $message], $data), 200);
    }
}
