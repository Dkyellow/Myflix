<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Room {
    public static function generateCode(int $length = 6): string {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    public static function create(int $movieId, string $roomName, ?int $hostUserId, string $hostSessionId, int $maxParticipants = 4): array {
        $pdo = Database::getInstance();
        $code = self::generateCode();

        // Ensure unique room code
        while (self::findByCode($code) !== null) {
            $code = self::generateCode();
        }

        $nowMs = (int)(microtime(true) * 1000);
        $stmt = $pdo->prepare("INSERT INTO watch_rooms (room_code, room_name, host_user_id, host_session_id, movie_id, status, playback_position, playback_state, last_playback_update, max_participants) VALUES (:room_code, :room_name, :host_user_id, :host_session_id, :movie_id, 'active', 0.0, 'paused', :last_playback_update, :max_participants)");
        $stmt->execute([
            'room_code' => $code,
            'room_name' => trim($roomName) ?: 'Movie Night',
            'host_user_id' => $hostUserId,
            'host_session_id' => $hostSessionId,
            'movie_id' => $movieId,
            'last_playback_update' => $nowMs,
            'max_participants' => min(max($maxParticipants, 2), 4)
        ]);

        $id = (int)$pdo->lastInsertId();
        return self::findById($id);
    }

    /**
     * Rooms someone can walk into right now: still active, currently occupied
     * (a participant heartbeated recently — same 45s window as
     * Participant::getActiveInRoom) and with at least one free slot.
     *
     * @return array<int, array>
     */
    public static function findAvailable(int $limit = 8): array {
        $pdo = Database::getInstance();
        $cutoffMs = (int)((microtime(true) - 45) * 1000);
        $limit = max(1, min(24, (int)$limit));

        // The inner join on room_participants is what drops abandoned rooms:
        // nobody with a fresh last_seen means no row, so no room.
        $sql = "SELECT r.id, r.room_code, r.room_name, r.max_participants, r.status,
                       r.playback_state, r.playback_position, r.updated_at,
                       m.title AS movie_title,
                       m.poster_url AS movie_poster,
                       m.backdrop_url AS movie_backdrop,
                       m.genre AS movie_genre,
                       m.duration_seconds AS movie_duration,
                       COUNT(p.id) AS participant_count
                FROM watch_rooms r
                JOIN movies m ON m.id = r.movie_id
                JOIN room_participants p
                  ON p.room_id = r.id
                 AND p.left_at IS NULL
                 AND p.last_seen >= :cutoff
                WHERE r.status = 'active'
                GROUP BY r.id
                HAVING COUNT(p.id) < r.max_participants
                ORDER BY participant_count DESC, r.updated_at DESC
                LIMIT {$limit}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['cutoff' => $cutoffMs]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT r.*, m.title as movie_title, m.poster_url as movie_poster, m.backdrop_url as movie_backdrop, m.video_url as movie_video_url, m.duration_seconds as movie_duration, m.genre as movie_genre FROM watch_rooms r JOIN movies m ON r.movie_id = m.id WHERE r.id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $room = $stmt->fetch();
        return $room ?: null;
    }

    public static function findByCode(string $code): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT r.*, m.title as movie_title, m.poster_url as movie_poster, m.backdrop_url as movie_backdrop, m.video_url as movie_video_url, m.duration_seconds as movie_duration, m.genre as movie_genre FROM watch_rooms r JOIN movies m ON r.movie_id = m.id WHERE r.room_code = :code LIMIT 1");
        $stmt->execute(['code' => strtoupper(trim($code))]);
        $room = $stmt->fetch();
        return $room ?: null;
    }

    public static function updatePlayback(int $roomId, string $state, float $position): void {
        $pdo = Database::getInstance();
        $nowMs = (int)(microtime(true) * 1000);
        $stmt = $pdo->prepare("UPDATE watch_rooms SET playback_state = :state, playback_position = :pos, last_playback_update = :now_ms, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute([
            'state' => in_array($state, ['playing', 'paused', 'buffering']) ? $state : 'paused',
            'pos' => max(0.0, $position),
            'now_ms' => $nowMs,
            'id' => $roomId
        ]);
    }

    public static function getCurrentEstimatedPosition(array $room): float {
        $pos = (float)$room['playback_position'];
        if ($room['playback_state'] === 'playing' && !empty($room['last_playback_update'])) {
            $nowMs = (int)(microtime(true) * 1000);
            $elapsedSeconds = max(0, ($nowMs - (int)$room['last_playback_update']) / 1000.0);
            $pos += $elapsedSeconds;
            if (!empty($room['movie_duration']) && $pos > $room['movie_duration']) {
                $pos = (float)$room['movie_duration'];
            }
        }
        return round($pos, 2);
    }

    public static function endRoom(int $roomId): void {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE watch_rooms SET status = 'ended', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['id' => $roomId]);
    }
}
