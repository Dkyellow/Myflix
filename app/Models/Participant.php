<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Participant {
    public static function join(int $roomId, ?int $userId, string $sessionId, string $displayName, ?string $avatarColor, bool $isHost): array {
        $pdo = Database::getInstance();
        $nowMs = (int)(microtime(true) * 1000);

        // Check if already in room
        $stmt = $pdo->prepare("SELECT * FROM room_participants WHERE room_id = :room_id AND session_id = :session_id LIMIT 1");
        $stmt->execute(['room_id' => $roomId, 'session_id' => $sessionId]);
        $existing = $stmt->fetch();

        $colors = ['#E50914', '#D81F26', '#E52D27', '#E74C3C', '#C0392B', '#E67E22'];
        $color = $avatarColor ?: $colors[abs(crc32($sessionId)) % count($colors)];

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE room_participants SET display_name = :name, avatar_color = :color, is_host = :is_host, last_seen = :now_ms, left_at = NULL WHERE id = :id");
            $stmt->execute([
                'name' => trim($displayName),
                'color' => $color,
                'is_host' => $isHost ? 1 : (int)$existing['is_host'],
                'now_ms' => $nowMs,
                'id' => $existing['id']
            ]);
            return self::findById((int)$existing['id']);
        }

        // Check participant limit (max 4 active)
        $active = self::getActiveInRoom($roomId);
        if (count($active) >= 4) {
            throw new \Exception("Room is full. Maximum 4 participants allowed.");
        }

        $stmt = $pdo->prepare("INSERT INTO room_participants (room_id, user_id, session_id, display_name, avatar_color, is_host, mic_muted, cam_muted, last_seen) VALUES (:room_id, :user_id, :session_id, :display_name, :avatar_color, :is_host, 0, 0, :last_seen)");
        $stmt->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'display_name' => trim($displayName),
            'avatar_color' => $color,
            'is_host' => $isHost ? 1 : 0,
            'last_seen' => $nowMs
        ]);

        $id = (int)$pdo->lastInsertId();
        return self::findById($id);
    }

    public static function findById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM room_participants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $p = $stmt->fetch();
        return $p ?: null;
    }

    public static function getActiveInRoom(int $roomId, int $timeoutSeconds = 45): array {
        $pdo = Database::getInstance();
        $cutoffMs = (int)((microtime(true) - $timeoutSeconds) * 1000);
        $stmt = $pdo->prepare("SELECT * FROM room_participants WHERE room_id = :room_id AND left_at IS NULL AND last_seen >= :cutoff ORDER BY is_host DESC, id ASC");
        $stmt->execute([
            'room_id' => $roomId,
            'cutoff' => $cutoffMs
        ]);
        return $stmt->fetchAll();
    }

    public static function heartbeat(int $roomId, string $sessionId, ?int $micMuted = null, ?int $camMuted = null): void {
        $pdo = Database::getInstance();
        $nowMs = (int)(microtime(true) * 1000);

        if ($micMuted !== null && $camMuted !== null) {
            $stmt = $pdo->prepare("UPDATE room_participants SET last_seen = :now_ms, mic_muted = :mic, cam_muted = :cam, left_at = NULL WHERE room_id = :room_id AND session_id = :session_id");
            $stmt->execute([
                'now_ms' => $nowMs,
                'mic' => $micMuted,
                'cam' => $camMuted,
                'room_id' => $roomId,
                'session_id' => $sessionId
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE room_participants SET last_seen = :now_ms, left_at = NULL WHERE room_id = :room_id AND session_id = :session_id");
            $stmt->execute([
                'now_ms' => $nowMs,
                'room_id' => $roomId,
                'session_id' => $sessionId
            ]);
        }
    }

    public static function leave(int $roomId, string $sessionId): void {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE room_participants SET left_at = CURRENT_TIMESTAMP WHERE room_id = :room_id AND session_id = :session_id");
        $stmt->execute([
            'room_id' => $roomId,
            'session_id' => $sessionId
        ]);
    }
}
