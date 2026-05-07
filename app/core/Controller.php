<?php

namespace App\Core;

use App\Database\Database;

/**
 * Base Controller
 * All controllers should extend this class
 */
class Controller
{
    protected Database $db;
    protected string $viewPath = __DIR__ . '/../views/';

    public function __construct()
    {
        $this->db = $this->getDatabase();
    }

    /**
     * Get database instance
     */
    protected function getDatabase(): Database
    {
        static $db = null;

        if ($db === null) {
            $config = require __DIR__ . '/../../config/database.php';
            $db = new Database($config);
        }

        return $db;
    }

    /**
     * Render a view with data
     */
    protected function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewPath . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            $this->error(404, "View not found: $view");
            return;
        }

        extract($data);
        require $viewFile;
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Show error page
     */
    protected function error(int $code, string $message = ''): void
    {
        http_response_code($code);
        
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ];

        $title = $messages[$code] ?? 'Error';
        $message = $message ?: $title;

        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>$code - $title</title>
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
                <h1>$code - $title</h1>
                <p>$message</p>
                <a href="/">← Back to Home</a>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }

    /**
     * Redirect to another URL
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }

    /**
     * Get request input
     */
    protected function input(string $key, $default = null)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if ($method === 'GET') {
            return $_GET[$key] ?? $default;
        } elseif ($method === 'POST') {
            return $_POST[$key] ?? $default;
        }
        
        return $default;
    }

    /**
     * Get all input
     */
    protected function allInput(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return $method === 'GET' ? $_GET : $_POST;
    }

    /**
     * Validate input
     */
    protected function validate(array $rules): array
    {
        $errors = [];
        $data = $this->allInput();

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';

            if ($rule === 'required' && empty($value)) {
                $errors[$field] = "$field is required";
            } elseif ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Invalid email format";
            }
        }

        return $errors;
    }

    /**
     * Log activity
     */
    protected function logActivity(int $userId, string $action, string $resourceType, int $resourceId = 0, array $details = []): void
    {
        $query = "INSERT INTO activity_logs (user_id, action, resource_type, resource_id, details) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $this->db->execute($query, [
            $userId,
            $action,
            $resourceType,
            $resourceId,
            json_encode($details)
        ]);
    }
}

// Helper function to access controller
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}
