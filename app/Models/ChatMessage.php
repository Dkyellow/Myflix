<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ChatMessage {
    public static function create(int $roomId, ?int $userId, string $displayName, string $avatarColor, string $message, bool $isSystem = false): array {
        $pdo = Database::getInstance();
        $nowMs = (int)(microtime(true) * 1000);

        $stmt = $pdo->prepare("INSERT INTO chat_messages (room_id, user_id, display_name, avatar_color, message, is_system, created_at_ms) VALUES (:room_id, :user_id, :display_name, :avatar_color, :message, :is_system, :created_at_ms)");
        $stmt->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
            'display_name' => trim($displayName),
            'avatar_color' => $avatarColor,
            'message' => trim($message),
            'is_system' => $isSystem ? 1 : 0,
            'created_at_ms' => $nowMs
        ]);

        $id = (int)$pdo->lastInsertId();
        return [
            'id' => $id,
            'room_id' => $roomId,
            'user_id' => $userId,
            'display_name' => trim($displayName),
            'avatar_color' => $avatarColor,
            'message' => trim($message),
            'is_system' => $isSystem ? 1 : 0,
            'created_at_ms' => $nowMs,
            'time_formatted' => date('g:i A')
        ];
    }

    public static function getForRoom(int $roomId, int $sinceMs = 0, int $limit = 50): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, room_id, user_id, display_name, avatar_color, message, is_system, created_at_ms, created_at FROM chat_messages WHERE room_id = :room_id AND created_at_ms > :since_ms ORDER BY created_at_ms ASC LIMIT :lim");
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':since_ms', $sinceMs, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['time_formatted'] = date('g:i A', (int)($row['created_at_ms'] / 1000));
        }
        return $rows;
    }
}
