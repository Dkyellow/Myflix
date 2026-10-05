<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User {
    public static function findById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, username, email, avatar_color, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByUsername(string $username): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => trim($username)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $username, string $email, string $password): array {
        $pdo = Database::getInstance();
        $colors = ['#E50914', '#B81D24', '#E52D27', '#FF3333', '#C0392B', '#962D3E'];
        $avatarColor = $colors[array_rand($colors)];

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, avatar_color) VALUES (:username, :email, :password_hash, :avatar_color)");
        $stmt->execute([
            'username' => trim($username),
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'avatar_color' => $avatarColor
        ]);

        $id = (int)$pdo->lastInsertId();
        return [
            'id' => $id,
            'username' => trim($username),
            'email' => strtolower(trim($email)),
            'avatar_color' => $avatarColor
        ];
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
