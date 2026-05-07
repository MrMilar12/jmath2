<?php

namespace App\Auth;

use App\Database\Database;
use App\Models\User;

/**
 * Authentication Class
 * Handles user login, registration, and session management
 */
class Auth
{
    private Database $db;
    private User $userModel;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        // Validate input
        $errors = $this->validateRegistration($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if email exists
        if ($this->userModel->findByEmail($data['email'])) {
            return ['success' => false, 'errors' => ['email' => 'Email already exists']];
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        // Create user
        $userId = $this->userModel->create([
            'role' => $data['role'] ?? 'student',
            'email' => $data['email'],
            'username' => $data['username'] ?? null,
            'student_id' => $data['student_id'] ?? null,
            'password_hash' => $passwordHash,
            'display_name' => $data['display_name']
        ]);

        if ($userId) {
            return ['success' => true, 'user_id' => $userId];
        }

        return ['success' => false, 'errors' => ['database' => 'Failed to create user']];
    }

    /**
     * Authenticate user with email/username and password
     */
    public function login(string $emailOrUsername, string $password): array
    {
        $user = $this->userModel->findByEmailOrUsername($emailOrUsername);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Account is inactive'];
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Start session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['logged_in'] = true;

        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->userModel->findById($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     */
    public function getCurrentUserRole(): ?string
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->getCurrentUserRole() === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $currentRole = $this->getCurrentUserRole();
        return in_array($currentRole, $roles);
    }

    /**
     * Validate registration input
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Passwords do not match';
        }

        if (empty($data['display_name'])) {
            $errors['display_name'] = 'Display name is required';
        }

        return $errors;
    }

    /**
     * Request password reset token
     */
    public function requestPasswordReset(string $email): array
    {
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            // Don't reveal if email exists or not (security best practice)
            return ['success' => true, 'message' => 'Check your email for password reset link'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
        $this->db->execute($query, [$user['id'], $token, $expiresAt]);

        // TODO: Send email with reset link

        return ['success' => true, 'message' => 'Password reset link sent to your email'];
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $query = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used_at IS NULL";
        $reset = $this->db->query($query, [$token])->fetch();

        if (!$reset) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE users SET password_hash = ? WHERE id = ?";
        $this->db->execute($updateQuery, [$passwordHash, $reset['user_id']]);

        $markUsedQuery = "UPDATE password_resets SET used_at = NOW() WHERE id = ?";
        $this->db->execute($markUsedQuery, [$reset['id']]);

        return ['success' => true, 'message' => 'Password has been reset successfully'];
    }
}
