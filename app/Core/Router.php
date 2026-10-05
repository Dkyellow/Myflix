<?php

namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $path, array|callable $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array|callable $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array|callable $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, array|callable $handler): void {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . rtrim($pattern, '/') . "/?$#";
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch(Request $request): void {
        $requestMethod = $request->getMethod();
        $requestPath = rtrim($request->getPath(), '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $requestPath, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func($handler, $request, ...array_values($params));
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    call_user_func([$controller, $method], $request, ...array_values($params));
                    return;
                }
            }
        }

        // 404 handling
        if ($request->isJson() || str_starts_with($requestPath, '/api/')) {
            Response::error('Endpoint not found', 404);
        } else {
            Response::view('404', ['title' => 'Page Not Found - MyFlix'], 404);
        }
    }
}
