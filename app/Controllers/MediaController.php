<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Streams uploaded videos with HTTP Range support.
 *
 * The PHP built-in server ignores Range headers and serves one request at a
 * time, so letting it hand out whole files would (a) break seeking and
 * (b) block chat/sync for as long as a movie takes to download. Every
 * response here is capped to CHUNK bytes, which keeps each request short
 * enough for other traffic to interleave between chunks.
 */
class MediaController {
    private const CHUNK = 4 * 1024 * 1024;

    private const MIME = [
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
    ];

    public function stream(Request $request, string $file): void {
        if (!preg_match('/^[A-Za-z0-9_\-]+\.(mp4|m4v|webm|ogv|ogg|mov)$/', $file)) {
            Response::error('Not found', 404);
        }

        $path = dirname(__DIR__, 2) . '/storage/uploads/videos/' . $file;
        if (!is_file($path)) {
            Response::error('Not found', 404);
        }

        $size = filesize($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $start = 0;
        $end = $size - 1;
        $partial = false;

        $range = $request->header('Range');
        if ($range && preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $m) && ($m[1] !== '' || $m[2] !== '')) {
            if ($m[1] === '') {
                // suffix range: last N bytes
                $start = max(0, $size - (int)$m[2]);
            } else {
                $start = (int)$m[1];
            }

            if ($start >= $size) {
                header("Content-Range: bytes */{$size}");
                http_response_code(416);
                exit;
            }

            $end = $m[1] !== '' && $m[2] !== '' ? (int)$m[2] : $size - 1;
            $end = min($end, $size - 1);
        }

        // Cap the response so no single request monopolises the server.
        $cappedEnd = min($end, $start + self::CHUNK - 1);
        $partial = $cappedEnd < $size - 1 || $start > 0;
        $end = $cappedEnd;
        $length = $end - $start + 1;

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        http_response_code($partial ? 206 : 200);
        header('Content-Type: ' . (self::MIME[$ext] ?? 'application/octet-stream'));
        header('Accept-Ranges: bytes');
        header("Content-Length: {$length}");
        header('Cache-Control: private, max-age=3600');
        header('Content-Disposition: inline; filename="' . rawurlencode($file) . '"');
        if ($partial) {
            header("Content-Range: bytes {$start}-{$end}/{$size}");
        }

        $fh = fopen($path, 'rb');
        if ($fh === false) {
            Response::error('Unable to read file', 500);
        }

        fseek($fh, $start);

        $remaining = $length;
        while ($remaining > 0 && !feof($fh)) {
            if (connection_aborted()) {
                break;
            }
            $chunk = fread($fh, min(65536, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }

        fclose($fh);
        exit;
    }
}
