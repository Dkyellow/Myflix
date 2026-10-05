<?php

namespace App\Core;

class Session {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['session_id'])) {
            $_SESSION['session_id'] = bin2hex(random_bytes(16));
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function getId(): string {
        self::start();
        return $_SESSION['session_id'];
    }

    public static function getCsrfToken(): string {
        self::start();
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(?string $token): bool {
        self::start();
        if (!$token || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function setUser(array $user): void {
        self::start();
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'avatar_color' => $user['avatar_color'] ?? '#E50914'
        ];
    }

    public static function getUser(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool {
        return self::getUser() !== null;
    }
}
