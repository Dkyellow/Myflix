<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Movie;

class UploadController {
    private const VIDEO_EXTENSIONS = ['mp4', 'm4v', 'webm', 'ogv', 'ogg', 'mov'];
    private const VIDEO_MIMES = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-m4v', 'video/mp2t'];
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function store(Request $request): void {
        $user = Session::getUser();
        if (!$user) {
            Response::error('Sign in to upload movies', 401);
        }

        if (!Session::validateCsrf($request->header('X-CSRF-Token'))) {
            Response::error('Your session token is missing or expired. Refresh the page and try again.', 403);
        }

        // post_max_size exceeded: PHP drops both $_POST and $_FILES
        if (empty($_FILES) && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            Response::error(
                'Upload is larger than post_max_size (' . ini_get('post_max_size') . '). Restart the server with serve.bat to raise it.',
                413
            );
        }

        $video = $_FILES['video'] ?? null;
        if (!$video || ($video['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Response::error('Choose a video file to upload', 422);
        }

        if ($video['error'] === UPLOAD_ERR_INI_SIZE || $video['error'] === UPLOAD_ERR_FORM_SIZE) {
            Response::error(
                'Video exceeds upload_max_filesize (' . ini_get('upload_max_filesize') . '). Restart the server with serve.bat to raise it.',
                413
            );
        }
        if ($video['error'] !== UPLOAD_ERR_OK) {
            Response::error('Upload failed (PHP error code ' . $video['error'] . ')', 500);
        }

        $extension = strtolower(pathinfo((string)$video['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::VIDEO_EXTENSIONS, true)) {
            Response::error('Unsupported format. Use MP4, WebM, MOV, M4V or OGV.', 422);
        }

        $mime = $this->detectMime($video['tmp_name'], $extension);
        if (!in_array($mime, self::VIDEO_MIMES, true)) {
            Response::error('That file does not look like a video (detected: ' . $mime . ').', 422);
        }

        $title = trim((string)$request->input('title', ''));
        if ($title === '') {
            $title = pathinfo((string)$video['name'], PATHINFO_FILENAME);
        }
        $title = substr(strip_tags($title), 0, 255);
        if ($title === '') {
            Response::error('Give the movie a title', 422);
        }

        $duration = (int)round((float)$request->input('duration', 0));
        if ($duration < 1) {
            Response::error('Could not read the video length. Re-select the file and try again.', 422);
        }

        $videoDir = dirname(__DIR__, 2) . '/storage/uploads/videos';
        $posterDir = dirname(__DIR__, 2) . '/public/uploads/posters';
        $this->ensureDir($videoDir);
        $this->ensureDir($posterDir);

        $basename = bin2hex(random_bytes(8));
        $videoName = $basename . '.' . $extension;
        $videoPath = $videoDir . '/' . $videoName;

        if (!move_uploaded_file($video['tmp_name'], $videoPath)) {
            Response::error('Could not save the uploaded video. Check that storage/uploads/ is writable.', 500);
        }

        $posterName = $this->storePoster($basename, $posterDir);
        $posterUrl = $posterName ? '/uploads/posters/' . $posterName : '/assets/img/no-poster.svg';

        $genre = substr(strip_tags((string)$request->input('genre', '')), 0, 100);
        if ($genre === '') {
            $genre = 'Other';
        }

        $description = substr(strip_tags((string)$request->input('description', '')), 0, 2000);
        if ($description === '') {
            $description = 'Uploaded to MyFlix by ' . ($user['username'] ?? 'a member') . '.';
        }

        $releaseYear = (int)$request->input('release_year', 0);
        if ($releaseYear < 1888 || $releaseYear > date('Y') + 1) {
            $releaseYear = (int)date('Y');
        }

        $movie = Movie::createUpload([
            'title' => $title,
            'description' => $description,
            'poster_url' => $posterUrl,
            'backdrop_url' => $posterUrl,
            'video_url' => '/media/' . $videoName,
            'duration_seconds' => min($duration, 86400 * 6),
            'release_year' => $releaseYear,
            'age_rating' => 'NR',
            'genre' => $genre,
            'director' => null,
            'cast_members' => null,
            'owner_user_id' => (int)$user['id'],
        ]);

        Response::success(['movie' => $movie], 'Movie uploaded');
    }

    public function destroy(Request $request, string $id): void {
        $user = Session::getUser();
        if (!$user) {
            Response::error('Sign in first', 401);
        }
        if (!Session::validateCsrf($request->header('X-CSRF-Token'))) {
            Response::error('Your session token is missing or expired. Refresh the page and try again.', 403);
        }

        $movie = Movie::findById((int)$id);
        if (!$movie) {
            Response::error('Movie not found', 404);
        }
        if ((int)($movie['owner_user_id'] ?? 0) !== (int)$user['id']) {
            Response::error('You can only remove movies you uploaded', 403);
        }

        $deleted = Movie::deleteUpload((int)$id, (int)$user['id']);
        if (!$deleted) {
            Response::error('You can only remove movies you uploaded', 403);
        }

        $this->deleteFile(dirname(__DIR__, 2) . '/storage/uploads/videos/' . basename($deleted['video_url']));
        if (str_starts_with((string)$deleted['poster_url'], '/uploads/posters/')) {
            $this->deleteFile(dirname(__DIR__, 2) . '/public' . $deleted['poster_url']);
        }

        Response::success([], 'Movie removed');
    }

    private function storePoster(string $basename, string $posterDir): ?string {
        $poster = $_FILES['poster'] ?? null;
        if (!$poster || ($poster['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($poster['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $mime = $this->detectMime($poster['tmp_name'], 'jpg');
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
        if ($ext === null) {
            return null;
        }

        $name = $basename . '.' . $ext;
        if (!move_uploaded_file($poster['tmp_name'], $posterDir . '/' . $name)) {
            return null;
        }
        return $name;
    }

    private function detectMime(string $path, string $extension): string {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }
        $byExt = [
            'mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm',
            'ogv' => 'video/ogg', 'ogg' => 'video/ogg', 'mov' => 'video/quicktime',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
        ];
        return $byExt[$extension] ?? 'application/octet-stream';
    }

    private function ensureDir(string $dir): void {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            Response::error('Could not create upload directory: ' . $dir, 500);
        }
    }

    private function deleteFile(string $path): void {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
