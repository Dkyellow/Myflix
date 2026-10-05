<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Movie {
    public static function all(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM movies ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $m = $stmt->fetch();
        return $m ?: null;
    }

    public static function findBySlug(string $slug): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $m = $stmt->fetch();
        return $m ?: null;
    }

    public static function getFeatured(): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM movies WHERE featured = 1 LIMIT 1");
        $m = $stmt->fetch();
        if (!$m) {
            $stmt = $pdo->query("SELECT * FROM movies ORDER BY id ASC LIMIT 1");
            $m = $stmt->fetch();
        }
        return $m ?: null;
    }

    public static function getByCategory(string $category, int $limit = 10): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE category = :category ORDER BY id ASC LIMIT :lim");
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function search(string $query, int $limit = 20): array {
        $pdo = Database::getInstance();
        $term = '%' . trim($query) . '%';
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE title LIKE :t1 OR genre LIKE :t2 OR description LIKE :t3 OR cast_members LIKE :t4 LIMIT :lim");
        $stmt->bindValue(':t1', $term, PDO::PARAM_STR);
        $stmt->bindValue(':t2', $term, PDO::PARAM_STR);
        $stmt->bindValue(':t3', $term, PDO::PARAM_STR);
        $stmt->bindValue(':t4', $term, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
