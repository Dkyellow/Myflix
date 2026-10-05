<?php

namespace App\Core;

class App {
    private Router $router;
    private Request $request;

    public function __construct() {
        // Register simple PSR-4 autoloader
        spl_autoload_register(function ($class) {
            $prefix = 'App\\';
            $baseDir = dirname(__DIR__) . '/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        });

        // Initialize Session and Database
        Session::start();
        Database::getInstance();

        $this->router = new Router();
        $this->request = new Request();
    }

    public function getRouter(): Router {
        return $this->router;
    }

    public function run(): void {
        try {
            $this->router->dispatch($this->request);
        } catch (\Throwable $e) {
            error_log("Unhandled Application Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            if ($this->request->isJson() || str_starts_with($this->request->getPath(), '/api/')) {
                Response::error("An internal error occurred: " . ($this->isDebug() ? $e->getMessage() : 'Please try again later.'), 500);
            } else {
                Response::view('500', [
                    'title' => 'Error - MyFlix',
                    'message' => $this->isDebug() ? $e->getMessage() : 'A server error occurred. Please refresh or try again.',
                    'trace' => $this->isDebug() ? $e->getTraceAsString() : ''
                ], 500);
            }
        }
    }

    private function isDebug(): bool {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $env = strtolower((string)($config['app']['env'] ?? 'production'));

        // Stack traces and messages are only ever shown outside production,
        // even if APP_DEBUG was left on by mistake.
        return (bool)($config['app']['debug'] ?? false)
            && !in_array($env, ['production', 'prod'], true);
    }
}
