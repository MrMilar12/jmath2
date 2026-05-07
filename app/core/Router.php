<?php

namespace App\Core;

use Closure;

/**
 * Router
 * Handles routing for the application
 */
class Router
{
    private array $routes = [];
    private string $requestUri;
    private string $requestMethod;

    public function __construct()
    {
        $this->requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Register a GET route
     */
    public function get(string $uri, $handler): void
    {
        $this->registerRoute('GET', $uri, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $uri, $handler): void
    {
        $this->registerRoute('POST', $uri, $handler);
    }

    /**
     * Register a PUT route
     */
    public function put(string $uri, $handler): void
    {
        $this->registerRoute('PUT', $uri, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $uri, $handler): void
    {
        $this->registerRoute('DELETE', $uri, $handler);
    }

    /**
     * Register a route
     */
    private function registerRoute(string $method, string $uri, $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler,
            'pattern' => $this->uriToPattern($uri)
        ];
    }

    /**
     * Convert URI to regex pattern
     */
    private function uriToPattern(string $uri): string
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '/^' . str_replace('/', '\/', $pattern) . '$/';
    }

    /**
     * Dispatch the request
     */
    public function dispatch(): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->requestMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $this->requestUri, $matches)) {
                // Extract parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Call handler
                if (is_string($route['handler'])) {
                    $this->callControllerMethod($route['handler'], $params);
                } elseif ($route['handler'] instanceof Closure) {
                    call_user_func_array($route['handler'], $params);
                }

                return;
            }
        }

        // Route not found
        $this->notFound();
    }

    /**
     * Call controller method
     */
    private function callControllerMethod(string $handler, array $params): void
    {
        [$controller, $method] = explode('@', $handler);

        $controllerClass = "App\\Controllers\\" . $controller;

        if (!class_exists($controllerClass)) {
            $this->notFound();
            return;
        }

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $method)) {
            $this->notFound();
            return;
        }

        call_user_func_array([$controllerInstance, $method], $params);
    }

    /**
     * Handle 404 not found
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>404 - Page Not Found</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f5f5f5;
                    margin: 0;
                    padding: 20px;
                }
                .error-container {
                    max-width: 500px;
                    margin: 100px auto;
                    background: white;
                    padding: 40px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                h1 { color: #333; margin: 0 0 10px 0; }
                p { color: #666; }
                a { color: #667eea; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>404 - Page Not Found</h1>
                <p>The page you're looking for doesn't exist.</p>
                <a href="/">← Back to Home</a>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }
}
