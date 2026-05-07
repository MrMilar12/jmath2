<?php

namespace App\Middleware;

use App\Auth\Auth;

/**
 * Authentication Middleware
 * Ensures user is logged in before accessing protected routes
 */
class AuthMiddleware
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Check if user is authenticated
     */
    public function handle(): void
    {
        if (!$this->auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Check if user has required role
     */
    public function requireRole(string $role): void
    {
        $this->handle();
        
        if (!$this->auth->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
    }

    /**
     * Check if user has any of the required roles
     */
    public function requireAnyRole(array $roles): void
    {
        $this->handle();
        
        if (!$this->auth->hasAnyRole($roles)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
    }

    /**
     * Check if user is student
     */
    public function requireStudent(): void
    {
        $this->requireRole('student');
    }

    /**
     * Check if user is teacher or admin
     */
    public function requireTeacherOrAdmin(): void
    {
        $this->requireAnyRole(['teacher', 'admin']);
    }

    /**
     * Check if user is admin
     */
    public function requireAdmin(): void
    {
        $this->requireRole('admin');
    }
}
