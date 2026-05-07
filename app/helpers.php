<?php

/**
 * Helper Functions
 * Commonly used utility functions
 */

/**
 * Redirect to a URL
 */
function redirect(string $url, int $statusCode = 302): void
{
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Url encode
 */
function url_encode(string $url): string
{
    return urlencode($url);
}

/**
 * Route to URL
 */
function route(string $path): string
{
    return '/' . ltrim($path, '/');
}

/**
 * Asset URL
 */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

/**
 * Escape HTML output
 */
function escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function formatCurrency(float $amount, string $currency = 'PHP'): string
{
    return number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate(string $date, string $format = 'M d, Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Time ago (e.g., "2 hours ago")
 */
function timeAgo(string $timestamp): string
{
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;

    $periods = [
        'second' => 60,
        'minute' => 60 * 60,
        'hour' => 60 * 60 * 24,
        'day' => 60 * 60 * 24 * 7,
        'week' => 60 * 60 * 24 * 7 * 4,
        'month' => 60 * 60 * 24 * 7 * 4 * 12,
        'year' => 60 * 60 * 24 * 7 * 4 * 12 * 10,
    ];

    foreach ($periods as $period => $value) {
        if ($diff >= $value) {
            $time = floor($diff / $value);
            $period = $time > 1 ? $period . 's' : $period;
            return "$time $period ago";
        }
    }

    return 'Just now';
}

/**
 * Generate random string
 */
function randomString(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if email is valid
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if URL is valid
 */
function isValidUrl(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get file extension
 */
function getFileExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file upload is allowed
 */
function isAllowedFileType(string $filename, array $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']): bool
{
    $ext = getFileExtension($filename);
    return in_array($ext, $allowedTypes);
}

/**
 * Format file size
 */
function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = $bytes;

    foreach ($units as $unit) {
        if ($size < 1024) {
            return round($size, 2) . ' ' . $unit;
        }
        $size = $size / 1024;
    }

    return round($size, 2) . ' TB';
}

/**
 * Generate slug from text
 */
function slug(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Check if string contains
 */
function strContains(string $haystack, string $needle): bool
{
    return str_contains($haystack, $needle);
}

/**
 * Check if string starts with
 */
function strStartsWith(string $haystack, string $needle): bool
{
    return str_starts_with($haystack, $needle);
}

/**
 * Check if string ends with
 */
function strEndsWith(string $haystack, string $needle): bool
{
    return str_ends_with($haystack, $needle);
}

/**
 * Get base URL
 */
function baseUrl(): string
{
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return "{$scheme}://{$host}";
}

/**
 * Get current URL
 */
function currentUrl(): string
{
    return baseUrl() . $_SERVER['REQUEST_URI'];
}

/**
 * Flash message to session
 */
function flashMessage(string $key, string $message, string $type = 'info'): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get flash message from session
 */
function getFlashMessage(string $key): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }
    
    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user ID
 */
function getCurrentUserId(): ?int
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole(): ?string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['role'] ?? null;
}

/**
 * Check if user has role
 */
function hasRole(string $role): bool
{
    return getCurrentUserRole() === $role;
}

/**
 * Check if user has any role
 */
function hasAnyRole(array $roles): bool
{
    return in_array(getCurrentUserRole(), $roles);
}

/**
 * Log error to file
 */
function logError(string $message, string $context = ''): void
{
    $logDir = __DIR__ . '/../../storage/logs/';
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($context)) {
        $logMessage .= " | Context: $context";
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

/**
 * Sanitize input
 */
function sanitize(string $input, string $type = 'string'): string
{
    if ($type === 'email') {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    } elseif ($type === 'url') {
        return filter_var($input, FILTER_SANITIZE_URL);
    } elseif ($type === 'number') {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    } else {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Calculate percentage
 */
function percentage(int $value, int $total): float
{
    if ($total === 0) {
        return 0;
    }
    return round(($value / $total) * 100, 2);
}

/**
 * Determine badge level based on XP
 */
function getBadgeLevel(int $xp): string
{
    if ($xp < 100) {
        return 'Beginner';
    } elseif ($xp < 300) {
        return 'Novice';
    } elseif ($xp < 600) {
        return 'Intermediate';
    } elseif ($xp < 1000) {
        return 'Advanced';
    } else {
        return 'Expert';
    }
}

/**
 * Get badge icon by level
 */
function getBadgeIcon(string $level): string
{
    $icons = [
        'Beginner' => '🟢',
        'Novice' => '🔵',
        'Intermediate' => '🟡',
        'Advanced' => '🟠',
        'Expert' => '🔴'
    ];
    
    return $icons[$level] ?? '⭐';
}
