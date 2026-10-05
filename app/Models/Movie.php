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

    public static function getUploads(int $limit = 12): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE owner_user_id IS NOT NULL ORDER BY id DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function slugify(string $text): string {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return substr($slug ?: 'movie', 0, 200);
    }

    private static function uniqueSlug(PDO $pdo, string $base): string {
        $i = 1;
        while (true) {
            $candidate = $i === 1 ? $base : $base . '-' . $i;
            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM movies WHERE slug = :s");
            $stmt->execute(['s' => $candidate]);
            if ((int)$stmt->fetch()['c'] === 0) {
                return $candidate;
            }
            $i++;
        }
    }

    public static function createUpload(array $d): array {
        $pdo = Database::getInstance();
        $slug = self::uniqueSlug($pdo, self::slugify($d['title']));

        $stmt = $pdo->prepare("INSERT INTO movies (title, slug, tagline, description, poster_url, backdrop_url, video_url, duration_seconds, release_year, age_rating, match_percentage, genre, category, featured, director, cast_members, owner_user_id)
            VALUES (:title, :slug, :tagline, :description, :poster_url, :backdrop_url, :video_url, :duration_seconds, :release_year, :age_rating, 98, :genre, 'uploads', 0, :director, :cast_members, :owner_user_id)");

        $stmt->execute([
            'title' => $d['title'],
            'slug' => $slug,
            'tagline' => $d['tagline'] ?? null,
            'description' => $d['description'],
            'poster_url' => $d['poster_url'],
            'backdrop_url' => $d['backdrop_url'],
            'video_url' => $d['video_url'],
            'duration_seconds' => $d['duration_seconds'],
            'release_year' => $d['release_year'],
            'age_rating' => $d['age_rating'],
            'genre' => $d['genre'],
            'director' => $d['director'] ?? null,
            'cast_members' => $d['cast_members'] ?? null,
            'owner_user_id' => $d['owner_user_id'],
        ]);

        return self::findById((int)$pdo->lastInsertId());
    }

    /**
     * Deletes an upload owned by $userId together with every watch party
     * that referenced it, so no room is left pointing at a missing movie.
     */
    public static function deleteUpload(int $id, int $userId): ?array {
        $pdo = Database::getInstance();
        $movie = self::findById($id);
        if (!$movie || (int)($movie['owner_user_id'] ?? 0) !== $userId) {
            return null;
        }

        $pdo->beginTransaction();
        try {
            $ids = [];
            $stmt = $pdo->prepare("SELECT id FROM watch_rooms WHERE movie_id = :id");
            $stmt->execute(['id' => $id]);
            foreach ($stmt->fetchAll() as $row) {
                $ids[] = (int)$row['id'];
            }

            if ($ids) {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM chat_messages WHERE room_id IN ($in)")->execute($ids);
                $pdo->prepare("DELETE FROM room_participants WHERE room_id IN ($in)")->execute($ids);
                $pdo->prepare("DELETE FROM room_signals WHERE room_id IN ($in)")->execute($ids);
            }
            $pdo->prepare("DELETE FROM watch_rooms WHERE movie_id = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM user_movie_lists WHERE movie_id = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM movies WHERE id = :id")->execute(['id' => $id]);

            $pdo->commit();
            return $movie;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
