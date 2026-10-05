<?php

namespace App\Controllers;

use App\Core\LiveKitToken;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ChatMessage;
use App\Models\Movie;
use App\Models\Participant;
use App\Models\Room;

class RoomController {
    public function create(Request $request): void {
        $movieId = (int)$request->input('movie_id', 0);
        $roomName = trim($request->input('room_name', ''));
        $maxParticipants = (int)$request->input('max_participants', 4);

        if ($movieId <= 0) {
            Response::error('Please select a movie to watch', 422);
        }

        $movie = Movie::findById($movieId);
        if (!$movie) {
            Response::error('Selected movie was not found', 404);
        }

        if (empty($roomName)) {
            $roomName = $movie['title'] . " Party";
        }

        $user = Session::getUser();
        $sessionId = Session::getId();
        $room = Room::create($movieId, $roomName, $user['id'] ?? null, $sessionId, $maxParticipants);

        // Auto-join creator as host
        $displayName = $user['username'] ?? 'Host';
        $avatarColor = $user['avatar_color'] ?? '#E50914';
        
        $participant = Participant::join($room['id'], $user['id'] ?? null, $sessionId, $displayName, $avatarColor, true);

        // Send system join message
        ChatMessage::create($room['id'], null, 'System', '#E50914', "{$displayName} created the watch party.", true);

        Response::success([
            'room' => $room,
            'room_code' => $room['room_code'],
            'room_url' => "/room/" . $room['room_code'],
            'participant' => $participant
        ], 'Room created successfully');
    }

    public function show(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::view('404', ['title' => 'Watch Room Not Found — MyFlix'], 404);
        }

        if ($room['status'] === 'ended') {
            Response::view('room-ended', [
                'title' => 'Watch Party Ended — MyFlix',
                'room' => $room
            ]);
        }

        $sessionId = Session::getId();
        $user = Session::getUser();
        $isHost = ($room['host_session_id'] === $sessionId) || ($user && $room['host_user_id'] === $user['id']);

        // Check if user is already a registered participant in this session
        $activeParticipants = Participant::getActiveInRoom($room['id']);
        $currentParticipant = null;
        foreach ($activeParticipants as $p) {
            if ($p['session_id'] === $sessionId) {
                $currentParticipant = $p;
                break;
            }
        }

        // If not joined yet, render the Join / Pre-join Lobby
        if (!$currentParticipant && !$isHost) {
            Response::view('join', [
                'title' => 'Join Watch Party — ' . $room['room_name'],
                'room' => $room,
                'user' => $user,
                'isRoomFull' => count($activeParticipants) >= ($room['max_participants'] ?? 4)
            ]);
        }

        // If user is host and not in participant list, add them
        if (!$currentParticipant && $isHost) {
            $displayName = $user['username'] ?? 'Host';
            $avatarColor = $user['avatar_color'] ?? '#E50914';
            $currentParticipant = Participant::join($room['id'], $user['id'] ?? null, $sessionId, $displayName, $avatarColor, true);
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';

        Response::view('room', [
            'title' => $room['room_name'] . ' — MyFlix Cinema',
            'room' => $room,
            'user' => $user,
            'participant' => $currentParticipant,
            'isHost' => $isHost,
            'livekitUrl' => $config['livekit']['url'] ?? '',
            'estimatedPosition' => Room::getCurrentEstimatedPosition($room)
        ]);
    }

    public function join(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        if ($room['status'] === 'ended') {
            Response::error('This watch party has ended', 410);
        }

        $displayName = trim($request->input('display_name', ''));
        $user = Session::getUser();
        if (empty($displayName)) {
            $displayName = $user['username'] ?? 'Guest ' . rand(100, 999);
        }

        $sessionId = Session::getId();
        $isHost = ($room['host_session_id'] === $sessionId) || ($user && $room['host_user_id'] === $user['id']);

        try {
            $participant = Participant::join(
                $room['id'],
                $user['id'] ?? null,
                $sessionId,
                $displayName,
                $user['avatar_color'] ?? null,
                $isHost
            );

            // Announce join
            ChatMessage::create($room['id'], null, 'System', '#E50914', "{$displayName} joined the party.", true);

            Response::success([
                'participant' => $participant,
                'room' => $room
            ], 'Joined watch room');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getToken(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sessionId = Session::getId();
        $user = Session::getUser();
        $displayName = $request->query('name', $user['username'] ?? ('Guest_' . substr($sessionId, 0, 4)));

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $tokenGenerator = new LiveKitToken($config['livekit']['api_key'], $config['livekit']['api_secret']);
        
        $token = $tokenGenerator
            ->setIdentity($sessionId)
            ->setName($displayName)
            ->setMetadata(json_encode([
                'user_id' => $user['id'] ?? null,
                'avatar_color' => $user['avatar_color'] ?? '#E50914',
                'is_host' => ($room['host_session_id'] === $sessionId)
            ]))
            ->addGrant([
                'room' => 'myflix_' . $room['room_code'],
                'roomJoin' => true,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true
            ])
            ->toJwt();

        Response::json([
            'token' => $token,
            'ws_url' => $config['livekit']['url'] ?? '',
            'room_name' => 'myflix_' . $room['room_code'],
            'identity' => $sessionId,
            'display_name' => $displayName
        ]);
    }

    public function heartbeat(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sessionId = Session::getId();
        $micMuted = $request->input('mic_muted') !== null ? (int)$request->input('mic_muted') : null;
        $camMuted = $request->input('cam_muted') !== null ? (int)$request->input('cam_muted') : null;

        Participant::heartbeat($room['id'], $sessionId, $micMuted, $camMuted);
        $participants = Participant::getActiveInRoom($room['id']);

        Response::json([
            'success' => true,
            'participants' => $participants,
            'count' => count($participants)
        ]);
    }

    public function leave(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sessionId = Session::getId();
        $user = Session::getUser();
        $name = $user['username'] ?? 'A participant';

        Participant::leave($room['id'], $sessionId);
        ChatMessage::create($room['id'], null, 'System', '#E50914', "{$name} left the room.", true);

        Response::success([], 'Left room');
    }

    public function sendSignal(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sessionId = Session::getId();
        $toSession = $request->input('to_session', '');
        $type = $request->input('type', '');
        $payload = $request->input('payload', '');

        if (!$toSession || !$type || !$payload) {
            Response::error('Missing signal parameters', 422);
        }

        $pdo = \App\Core\Database::getInstance();
        $nowMs = (int)(microtime(true) * 1000);
        $stmt = $pdo->prepare("INSERT INTO room_signals (room_id, from_session, to_session, type, payload, created_at_ms) VALUES (:room_id, :from_session, :to_session, :type, :payload, :now_ms)");
        $stmt->execute([
            'room_id' => $room['id'],
            'from_session' => $sessionId,
            'to_session' => $toSession,
            'type' => $type,
            'payload' => is_string($payload) ? $payload : json_encode($payload),
            'now_ms' => $nowMs
        ]);

        Response::success([], 'Signal dispatched');
    }

    public function getSignals(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sessionId = Session::getId();
        $pdo = \App\Core\Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, from_session, to_session, type, payload, created_at_ms FROM room_signals WHERE room_id = :room_id AND to_session = :to_session ORDER BY id ASC");
        $stmt->execute([
            'room_id' => $room['id'],
            'to_session' => $sessionId
        ]);
        $signals = $stmt->fetchAll();

        if (!empty($signals)) {
            $del = $pdo->prepare("DELETE FROM room_signals WHERE room_id = :room_id AND to_session = :to_session");
            $del->execute(['room_id' => $room['id'], 'to_session' => $sessionId]);
        }

        Response::json(['signals' => $signals]);
    }

    public function end(Request $request, string $code): void {
        $room = Room::findByCode($code);
        if (!$room) {
            Response::error('Room not found', 404);
        }

        $sessionId = Session::getId();
        $user = Session::getUser();
        $isHost = ($room['host_session_id'] === $sessionId) || ($user && $room['host_user_id'] === $user['id']);

        if (!$isHost) {
            Response::error('Only the host can end the party', 403);
        }

        Room::endRoom($room['id']);
        ChatMessage::create($room['id'], null, 'System', '#E50914', "The host has ended this watch party.", true);

        Response::success([], 'Party ended');
    }
}
