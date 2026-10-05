<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserList {
    public static function toggle(?int $userId, string $sessionId, int $movieId): bool {
        $pdo = Database::getInstance();
        
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM user_movie_lists WHERE (user_id = :user_id OR (:uid_null AND session_id = :session_id)) AND movie_id = :movie_id LIMIT 1");
        $stmt->execute([
            'user_id' => $userId,
            'uid_null' => $userId === null ? 1 : 0,
            'session_id' => $sessionId,
            'movie_id' => $movieId
        ]);
        $existing = $stmt->fetch();

        if ($existing) {
            $del = $pdo->prepare("DELETE FROM user_movie_lists WHERE id = :id");
            $del->execute(['id' => $existing['id']]);
            return false; // Removed
        } else {
            $ins = $pdo->prepare("INSERT INTO user_movie_lists (user_id, session_id, movie_id) VALUES (:user_id, :session_id, :movie_id)");
            $ins->execute([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'movie_id' => $movieId
            ]);
            return true; // Added
        }
    }

    public static function isInList(?int $userId, string $sessionId, int $movieId): bool {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id FROM user_movie_lists WHERE (user_id = :user_id OR (:uid_null AND session_id = :session_id)) AND movie_id = :movie_id LIMIT 1");
        $stmt->execute([
            'user_id' => $userId,
            'uid_null' => $userId === null ? 1 : 0,
            'session_id' => $sessionId,
            'movie_id' => $movieId
        ]);
        return (bool)$stmt->fetch();
    }

    public static function getMovies(?int $userId, string $sessionId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT m.* FROM movies m JOIN user_movie_lists l ON m.id = l.movie_id WHERE (l.user_id = :user_id OR (:uid_null AND l.session_id = :session_id)) ORDER BY l.id DESC");
        $stmt->execute([
            'user_id' => $userId,
            'uid_null' => $userId === null ? 1 : 0,
            'session_id' => $sessionId
        ]);
        return $stmt->fetchAll();
    }
}
