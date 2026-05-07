<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function secure_headers(): void
{
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:;");
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function make_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\s-]/', '', $value) ?? '';
    $value = preg_replace('/[\s-]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function sanitize_slug(string $slug): string
{
    return preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
}

function redirect_to(string $path): void
{
    header('Location: ' . $path, true, 302);
    exit;
}

function is_student_logged_in(): bool
{
    return isset($_SESSION['student']) && is_array($_SESSION['student']) && isset($_SESSION['student']['id']);
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']) && isset($_SESSION['admin']['id']);
}

function get_level_from_xp(int $xp): string
{
    if ($xp >= 1000) {
        return 'Advanced';
    }
    if ($xp >= 400) {
        return 'Intermediate';
    }
    return 'Beginner';
}
