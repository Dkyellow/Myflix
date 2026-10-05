<?php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'sqlite';

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            $dbConf = $config['db'];

            if ($dbConf['connection'] === 'mysql') {
                try {
                    $pdo = self::connectMysql($dbConf, true);
                    self::$instance = $pdo;
                    self::$driver = 'mysql';
                } catch (PDOException $e) {
                    // The named database may not exist yet. Creating it needs the
                    // CREATE privilege, which shared hosts (cPanel) withhold, so
                    // this second attempt is allowed to fail: we then fall through
                    // to SQLite rather than dying. Configuring MySQL and silently
                    // landing on SQLite is worse, so log it loudly.
                    try {
                        $admin = self::connectMysql($dbConf, false);
                        $db = self::identifier($dbConf['database']);
                        $admin->exec(
                            "CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                        );
                        $admin->exec("USE `{$db}`");
                        self::$instance = $admin;
                        self::$driver = 'mysql';
                    } catch (PDOException $e2) {
                        error_log("MySQL unavailable ({$e2->getMessage()}), falling back to SQLite.");
                        self::connectSqlite();
                    }
                }
            } else {
                self::connectSqlite();
            }

            self::ensureTables();
        }

        return self::$instance;
    }

    private static function connectMysql(array $dbConf, bool $withDatabase): PDO {
        $dsn = "mysql:host={$dbConf['host']};port={$dbConf['port']};charset=utf8mb4";
        if ($withDatabase) {
            $dsn .= ';dbname=' . $dbConf['database'];
        }

        return new PDO($dsn, $dbConf['username'], $dbConf['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private static function identifier(string $name): string {
        return str_replace('`', '', $name);
    }

    private static function connectSqlite(): void {
        $storageDir = dirname(__DIR__, 2) . '/storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }
        $dbPath = $storageDir . '/database.sqlite';
        $pdo = new PDO("sqlite:" . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        // Concurrent requests otherwise die with "database is locked". Keep this
        // at or above the 60s pdo_sqlite default rather than lowering it.
        $pdo->exec("PRAGMA busy_timeout = 60000;");
        $pdo->exec("PRAGMA journal_mode = WAL;");
        $pdo->exec("PRAGMA synchronous = NORMAL;");
        self::$instance = $pdo;
        self::$driver = 'sqlite';
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    public static function ensureTables(): void {
        $pdo = self::$instance;
        $isSqlite = (self::$driver === 'sqlite');

        $autoInc = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
        $timestampDef = $isSqlite ? "DATETIME DEFAULT CURRENT_TIMESTAMP" : "DATETIME DEFAULT CURRENT_TIMESTAMP";

        // Users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id {$autoInc},
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(191) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            avatar_color VARCHAR(20) DEFAULT '#E50914',
            created_at {$timestampDef},
            updated_at {$timestampDef}
        )");

        // Movies table
        $pdo->exec("CREATE TABLE IF NOT EXISTS movies (
            id {$autoInc},
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            tagline VARCHAR(255) NULL,
            description TEXT NOT NULL,
            poster_url VARCHAR(500) NOT NULL,
            backdrop_url VARCHAR(500) NOT NULL,
            video_url VARCHAR(500) NOT NULL,
            duration_seconds INT NOT NULL DEFAULT 600,
            release_year INT NOT NULL DEFAULT 2024,
            age_rating VARCHAR(10) NOT NULL DEFAULT 'PG-13',
            match_percentage INT NOT NULL DEFAULT 98,
            genre VARCHAR(100) NOT NULL DEFAULT 'Action',
            category VARCHAR(50) NOT NULL DEFAULT 'trending',
            featured INT DEFAULT 0,
            director VARCHAR(100) NULL,
            cast_members VARCHAR(255) NULL,
            owner_user_id INT NULL,
            created_at {$timestampDef}
        )");

        // Migration: uploads ownership column (no-op once it exists)
        try {
            $pdo->exec("ALTER TABLE movies ADD COLUMN owner_user_id INT NULL");
        } catch (\Throwable $e) {
            // column already present
        }

        // Watch rooms table
        $pdo->exec("CREATE TABLE IF NOT EXISTS watch_rooms (
            id {$autoInc},
            room_code VARCHAR(32) NOT NULL UNIQUE,
            room_name VARCHAR(150) NOT NULL,
            host_user_id INT NULL,
            host_session_id VARCHAR(100) NOT NULL,
            movie_id INT NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            playback_position DOUBLE NOT NULL DEFAULT 0.0,
            playback_state VARCHAR(20) NOT NULL DEFAULT 'paused',
            last_playback_update BIGINT NOT NULL DEFAULT 0,
            max_participants INT NOT NULL DEFAULT 4,
            created_at {$timestampDef},
            updated_at {$timestampDef}
        )");

        // Room participants table
        $pdo->exec("CREATE TABLE IF NOT EXISTS room_participants (
            id {$autoInc},
            room_id INT NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(100) NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            avatar_color VARCHAR(20) DEFAULT '#E50914',
            is_host INT NOT NULL DEFAULT 0,
            mic_muted INT NOT NULL DEFAULT 0,
            cam_muted INT NOT NULL DEFAULT 0,
            joined_at {$timestampDef},
            last_seen BIGINT NOT NULL DEFAULT 0,
            left_at DATETIME NULL
        )");

        // Chat messages table
        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id {$autoInc},
            room_id INT NOT NULL,
            user_id INT NULL,
            display_name VARCHAR(100) NOT NULL,
            avatar_color VARCHAR(20) DEFAULT '#E50914',
            message TEXT NOT NULL,
            is_system INT NOT NULL DEFAULT 0,
            created_at_ms BIGINT NOT NULL,
            created_at {$timestampDef}
        )");

        // Room WebRTC signaling table
        $pdo->exec("CREATE TABLE IF NOT EXISTS room_signals (
            id {$autoInc},
            room_id INT NOT NULL,
            from_session VARCHAR(100) NOT NULL,
            to_session VARCHAR(100) NOT NULL,
            type VARCHAR(50) NOT NULL,
            payload TEXT NOT NULL,
            created_at_ms BIGINT NOT NULL
        )");

        // User movie lists (My List)
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_movie_lists (
            id {$autoInc},
            user_id INT NULL,
            session_id VARCHAR(100) NOT NULL,
            movie_id INT NOT NULL,
            created_at {$timestampDef}
        )");

        // Seed initial movies if table is empty
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM movies");
        $count = $stmt->fetch()['count'] ?? 0;
        if ($count == 0) {
            \App\Database\Seeds::seed($pdo);
        }
    }
}
