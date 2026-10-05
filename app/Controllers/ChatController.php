<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ChatMessage;
use App\Models\Room;

class ChatController {
    public function getMessages(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sinceMs = (int)$request->query('since_ms', 0);
        $messages = ChatMessage::getForRoom($room['id'], $sinceMs, 50);

        Response::json([
            'success' => true,
            'messages' => $messages,
            'count' => count($messages),
            'server_time' => (int)(microtime(true) * 1000)
        ]);
    }

    public function sendMessage(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        if ($room['status'] === 'ended') {
            Response::error('Party has ended', 410);
        }

        $text = trim($request->input('message', ''));
        if (empty($text)) {
            Response::error('Message cannot be empty', 422);
        }

        // Limit message length
        if (mb_strlen($text) > 500) {
            $text = mb_substr($text, 0, 500);
        }

        $user = Session::getUser();
        $sessionId = Session::getId();
        $displayName = $request->input('sender_name', $user['username'] ?? 'Guest');
        $avatarColor = $user['avatar_color'] ?? '#E50914';

        $msg = ChatMessage::create($room['id'], $user['id'] ?? null, $displayName, $avatarColor, $text, false);

        Response::success([
            'message' => $msg
        ], 'Message sent');
    }

    public function sendReaction(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $emoji = trim($request->input('emoji', '🍿'));
        $allowed = ['🍿', '❤️', '😂', '😱', '🔥', '👏', '🎬', '😮'];
        if (!in_array($emoji, $allowed)) {
            $emoji = '🍿';
        }

        $user = Session::getUser();
        $displayName = $request->input('sender_name', $user['username'] ?? 'Guest');
        $avatarColor = $user['avatar_color'] ?? '#E50914';

        // Broadcast reaction in chat
        $msg = ChatMessage::create($room['id'], $user['id'] ?? null, $displayName, $avatarColor, "reacted with {$emoji}", true);

        Response::success([
            'emoji' => $emoji,
            'message' => $msg
        ], 'Reaction sent');
    }
}
