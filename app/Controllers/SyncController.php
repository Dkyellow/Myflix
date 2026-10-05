<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ChatMessage;
use App\Models\Participant;
use App\Models\Room;

class SyncController {
    public function getState(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sessionId = Session::getId();
        $participants = Participant::getActiveInRoom($room['id']);
        $estimatedPos = Room::getCurrentEstimatedPosition($room);

        Response::json([
            'success' => true,
            'room_code' => $room['room_code'],
            'status' => $room['status'],
            'playback_state' => $room['playback_state'],
            'playback_position' => (float)$room['playback_position'],
            'estimated_position' => $estimatedPos,
            'last_playback_update' => (int)$room['last_playback_update'],
            'server_time' => (int)(microtime(true) * 1000),
            'participants' => $participants,
            'participant_count' => count($participants)
        ]);
    }

    public function updateState(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        if ($room['status'] === 'ended') {
            Response::error('Watch party has ended', 410);
        }

        $action = $request->input('action'); // 'play', 'pause', 'seek', 'sync'
        $position = (float)$request->input('position', 0.0);
        $user = Session::getUser();
        $sessionId = Session::getId();
        $displayName = $request->input('sender_name', $user['username'] ?? 'Someone');

        $newState = $room['playback_state'];
        $announcement = null;

        if ($action === 'play') {
            $newState = 'playing';
            $announcement = "{$displayName} played the movie.";
        } elseif ($action === 'pause') {
            $newState = 'paused';
            $announcement = "{$displayName} paused the movie.";
        } elseif ($action === 'seek') {
            $mins = floor($position / 60);
            $secs = floor($position % 60);
            $timeStr = sprintf("%02d:%02d", $mins, $secs);
            $announcement = "{$displayName} jumped to {$timeStr}.";
        } elseif ($action === 'sync') {
            // Re-sync request
        }

        Room::updatePlayback($room['id'], $newState, $position);

        if ($announcement) {
            ChatMessage::create($room['id'], $user['id'] ?? null, 'System', '#E50914', $announcement, true);
        }

        $updatedRoom = Room::findById($room['id']);

        Response::json([
            'success' => true,
            'action' => $action,
            'playback_state' => $updatedRoom['playback_state'],
            'playback_position' => (float)$updatedRoom['playback_position'],
            'estimated_position' => Room::getCurrentEstimatedPosition($updatedRoom),
            'last_playback_update' => (int)$updatedRoom['last_playback_update'],
            'server_time' => (int)(microtime(true) * 1000),
            'sender_session_id' => $sessionId
        ]);
    }

    /**
     * Non-blocking room synchronization & event endpoint.
     * Works with high speed on both development servers and production cPanel!
     */
    public function events(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        // Prevent session write lock
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $sessionId = Session::getId();
        $roomId = (int)$room['id'];
        $lastChatMs = (int)$request->query('since_ms', 0);

        $participants = Participant::getActiveInRoom($roomId);
        $newMessages = ChatMessage::getForRoom($roomId, $lastChatMs, 25);
        $estimatedPos = Room::getCurrentEstimatedPosition($room);

        // Fetch pending WebRTC signals
        $pdo = \App\Core\Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, from_session, to_session, type, payload, created_at_ms FROM room_signals WHERE room_id = :room_id AND to_session = :to_session ORDER BY id ASC");
        $stmt->execute(['room_id' => $roomId, 'to_session' => $sessionId]);
        $signals = $stmt->fetchAll();

        if (!empty($signals)) {
            $del = $pdo->prepare("DELETE FROM room_signals WHERE room_id = :room_id AND to_session = :to_session");
            $del->execute(['room_id' => $roomId, 'to_session' => $sessionId]);
        }

        Response::json([
            'success' => true,
            'room_code' => $room['room_code'],
            'status' => $room['status'],
            'playback_state' => $room['playback_state'],
            'playback_position' => (float)$room['playback_position'],
            'estimated_position' => $estimatedPos,
            'last_playback_update' => (int)$room['last_playback_update'],
            'server_time' => (int)(microtime(true) * 1000),
            'participants' => $participants,
            'messages' => $newMessages,
            'signals' => $signals,
            'last_chat_ms' => !empty($newMessages) ? (int)end($newMessages)['created_at_ms'] : $lastChatMs
        ]);
    }
}
