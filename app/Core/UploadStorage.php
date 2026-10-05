<?php

namespace App\Core;

/**
 * Locates and stores uploaded video files.
 *
 * Under Apache/LiteSpeed (cPanel) videos are written into public/uploads and
 * served as static files: the web server handles HTTP Range natively and a
 * playing movie does not occupy a PHP worker for its whole runtime. The PHP
 * built-in dev server has neither property, so there the files stay outside
 * the web root and are streamed by MediaController instead.
 */
final class UploadStorage {
    public const VIDEO_EXTENSIONS = ['mp4', 'm4v', 'webm', 'ogv', 'ogg', 'mov'];

    public const MIME = [
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
    ];

    public static function root(): string {
        return dirname(__DIR__, 2);
    }

    /** True when running under `php -S`, which serves no Range requests. */
    public static function usesPhpStream(): bool {
        return (bool)preg_match('/^php\s/i', $_SERVER['SERVER_SOFTWARE'] ?? '');
    }

    public static function videosDir(): string {
        return self::usesPhpStream()
            ? self::root() . '/storage/uploads/videos'
            : self::root() . '/public/uploads/videos';
    }

    public static function postersDir(): string {
        return self::root() . '/public/uploads/posters';
    }

    public static function publicUrl(string $basename, string $ext): string {
        return self::usesPhpStream()
            ? '/media/' . $basename
            : '/uploads/videos/' . $basename . '.' . $ext;
    }

    public static function storeVideo(string $tmpPath, string $basename, string $ext): bool {
        $dir = self::videosDir();
        self::ensureDir($dir);
        return move_uploaded_file($tmpPath, $dir . '/' . $basename . '.' . $ext);
    }

    /**
     * @param string $name bare basename, or basename plus an extension
     */
    public static function resolve(string $name): ?string {
        if (!preg_match('/^([A-Za-z0-9_\-]{1,64})(?:\.[A-Za-z0-9]{1,8})?$/', $name, $m)) {
            return null;
        }
        $stem = $m[1];

        $dirs = array_unique([
            self::videosDir(),
            self::root() . '/storage/uploads/videos',
            self::root() . '/public/uploads/videos',
        ]);

        foreach ($dirs as $dir) {
            foreach (glob($dir . '/' . $stem . '.*') ?: [] as $candidate) {
                $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                if (isset(self::MIME[$ext]) && is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    public static function resolveFromUrl(string $videoUrl): ?string {
        if (str_starts_with($videoUrl, '/media/')) {
            return self::resolve(basename($videoUrl));
        }
        if (str_starts_with($videoUrl, '/uploads/videos/')) {
            $direct = self::root() . '/public' . $videoUrl;
            if (is_file($direct)) {
                return $direct;
            }
            return self::resolve(pathinfo(basename($videoUrl), PATHINFO_FILENAME));
        }
        return null;
    }

    public static function deleteFromUrl(string $videoUrl): void {
        $path = self::resolveFromUrl($videoUrl);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    public static function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // Empty index file: stops directory listings even if the host has
        // Indexes turned on (we never rely on Options -Indexes, which some
        // shared hosts reject with a 500).
        if (is_dir($dir) && !is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }
    }
}
