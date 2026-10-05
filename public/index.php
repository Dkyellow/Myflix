<?php

declare(strict_types=1);

// Error reporting for development. Warnings and notices must never be printed
// to visitors on a hosted copy, so display_errors follows the same production
// guard that App uses before rendering stack traces.
$config = require dirname(__DIR__) . '/config/config.php';
$env = strtolower((string)($config['app']['env'] ?? 'production'));
$isDebug = (bool)($config['app']['debug'] ?? false) && !in_array($env, ['production', 'prod'], true);
ini_set('display_errors', $isDebug ? '1' : '0');
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/app/Core/App.php';

use App\Core\App;
use App\Controllers\AuthController;
use App\Controllers\MovieController;
use App\Controllers\RoomController;
use App\Controllers\SyncController;
use App\Controllers\ChatController;
use App\Controllers\MediaController;
use App\Controllers\UploadController;

$app = new App();
$router = $app->getRouter();

// Web Page Routes
$router->get('/', [MovieController::class, 'home']);
$router->get('/movie/{id}', [MovieController::class, 'detail']);
$router->get('/room/{code}', [RoomController::class, 'show']);

// Authentication API
$router->get('/api/auth/me', [AuthController::class, 'me']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);

// Movie API
$router->get('/api/movies/search', [MovieController::class, 'search']);
$router->get('/api/movie/{id}', [MovieController::class, 'detail']);
$router->post('/api/my-list/toggle', [MovieController::class, 'toggleMyList']);
$router->get('/api/my-list', [MovieController::class, 'getMyList']);

// User Uploads
$router->post('/api/movies/upload', [UploadController::class, 'store']);
$router->delete('/api/movies/{id}', [UploadController::class, 'destroy']);

// Uploaded video streaming (Range-aware)
$router->get('/media/{file}', [MediaController::class, 'stream']);

// Room Management & WebRTC Signaling API
$router->post('/api/rooms/create', [RoomController::class, 'create']);
$router->post('/api/rooms/{code}/join', [RoomController::class, 'join']);
$router->get('/api/rooms/{code}/token', [RoomController::class, 'getToken']);
$router->post('/api/rooms/{code}/heartbeat', [RoomController::class, 'heartbeat']);
$router->post('/api/rooms/{code}/signal', [RoomController::class, 'sendSignal']);
$router->get('/api/rooms/{code}/signals', [RoomController::class, 'getSignals']);
$router->post('/api/rooms/{code}/leave', [RoomController::class, 'leave']);
$router->post('/api/rooms/{code}/end', [RoomController::class, 'end']);

// Playback Sync API
$router->get('/api/rooms/{code}/sync', [SyncController::class, 'getState']);
$router->post('/api/rooms/{code}/sync', [SyncController::class, 'updateState']);
$router->get('/api/rooms/{code}/events', [SyncController::class, 'events']);

// Chat & Reaction API
$router->get('/api/rooms/{code}/chat', [ChatController::class, 'getMessages']);
$router->post('/api/rooms/{code}/chat', [ChatController::class, 'sendMessage']);
$router->post('/api/rooms/{code}/reaction', [ChatController::class, 'sendReaction']);

$app->run();
