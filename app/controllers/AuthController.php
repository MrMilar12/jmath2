<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Controller;

/**
 * Authentication Controller
 */
class AuthController extends Controller
{
    private Auth $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new Auth($this->db);
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if ($this->auth->isLoggedIn()) {
            $role = $this->auth->getCurrentUserRole();
            if ($role === 'student') {
                redirect('/student/dashboard');
            } else if ($role === 'teacher') {
                redirect('/teacher/dashboard');
            } else if ($role === 'admin') {
                redirect('/admin/dashboard');
            }
        }

        return $this->render('auth/login', [
            'title' => 'Login - jmath2'
        ]);
    }

    /**
     * Handle login POST request
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        $emailOrUsername = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($emailOrUsername) || empty($password)) {
            return $this->render('auth/login', [
                'title' => 'Login - jmath2',
                'error' => 'Email/Username and password are required'
            ]);
        }

        $result = $this->auth->login($emailOrUsername, $password);

        if (!$result['success']) {
            return $this->render('auth/login', [
                'title' => 'Login - jmath2',
                'error' => $result['error']
            ]);
        }

        // Redirect based on role
        $role = $this->auth->getCurrentUserRole();
        if ($role === 'student') {
            redirect('/student/dashboard');
        } else if ($role === 'teacher') {
            redirect('/teacher/dashboard');
        } else if ($role === 'admin') {
            redirect('/admin/dashboard');
        }
    }

    /**
     * Show registration form
     */
    public function showRegister()
    {
        // If already logged in, redirect to dashboard
        if ($this->auth->isLoggedIn()) {
            $role = $this->auth->getCurrentUserRole();
            if ($role === 'student') {
                redirect('/student/dashboard');
            } else if ($role === 'teacher') {
                redirect('/teacher/dashboard');
            } else if ($role === 'admin') {
                redirect('/admin/dashboard');
            }
        }

        $userType = $_GET['type'] ?? 'student';
        if (!in_array($userType, ['student', 'teacher', 'admin'])) {
            $userType = 'student';
        }

        return $this->render('auth/register', [
            'title' => 'Register - jmath2',
            'userType' => $userType
        ]);
    }

    /**
     * Handle registration POST request
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        $data = [
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'display_name' => $_POST['display_name'] ?? '',
            'role' => $_POST['role'] ?? 'student',
            'student_id' => $_POST['student_id'] ?? '',
            'username' => $_POST['username'] ?? null
        ];

        // Validate role
        if (!in_array($data['role'], ['student', 'teacher', 'admin'])) {
            $data['role'] = 'student';
        }

        $result = $this->auth->register($data);

        if (!$result['success']) {
            return $this->render('auth/register', [
                'title' => 'Register - jmath2',
                'userType' => $data['role'],
                'errors' => $result['errors'],
                'formData' => $data
            ]);
        }

        // Auto-login after registration
        $loginResult = $this->auth->login($data['email'], $data['password']);
        if ($loginResult['success']) {
            // Create initial leaderboard entry
            $query = "INSERT INTO leaderboard (user_id, total_xp, rank) VALUES (?, 0, 0)";
            $this->db->execute($query, [$result['user_id']]);

            // Create initial streak entry
            $query = "INSERT INTO student_daily_streaks (student_id) VALUES (?)";
            $this->db->execute($query, [$result['user_id']]);

            $role = $data['role'];
            if ($role === 'student') {
                redirect('/student/dashboard');
            } else if ($role === 'teacher') {
                redirect('/teacher/dashboard');
            } else if ($role === 'admin') {
                redirect('/admin/dashboard');
            }
        }

        return $this->render('auth/register', [
            'title' => 'Register - jmath2',
            'userType' => $data['role'],
            'message' => 'Registration successful. Please log in.'
        ]);
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        $this->auth->logout();
        redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword()
    {
        if ($this->auth->isLoggedIn()) {
            redirect('/student/dashboard');
        }

        return $this->render('auth/forgot-password', [
            'title' => 'Forgot Password - jmath2'
        ]);
    }

    /**
     * Handle forgot password POST request
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            return $this->render('auth/forgot-password', [
                'title' => 'Forgot Password - jmath2',
                'error' => 'Email is required'
            ]);
        }

        $result = $this->auth->requestPasswordReset($email);

        return $this->render('auth/forgot-password', [
            'title' => 'Forgot Password - jmath2',
            'message' => $result['message']
        ]);
    }

    /**
     * Show password reset form
     */
    public function showResetPassword()
    {
        if ($this->auth->isLoggedIn()) {
            redirect('/student/dashboard');
        }

        $token = $_GET['token'] ?? '';

        return $this->render('auth/reset-password', [
            'title' => 'Reset Password - jmath2',
            'token' => $token
        ]);
    }

    /**
     * Handle password reset POST request
     */
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($password) || $password !== $passwordConfirm) {
            return $this->render('auth/reset-password', [
                'title' => 'Reset Password - jmath2',
                'token' => $token,
                'error' => 'Passwords do not match or are empty'
            ]);
        }

        $result = $this->auth->resetPassword($token, $password);

        if (!$result['success']) {
            return $this->render('auth/reset-password', [
                'title' => 'Reset Password - jmath2',
                'token' => $token,
                'error' => $result['error']
            ]);
        }

        return $this->render('auth/reset-password-success', [
            'title' => 'Password Reset - jmath2'
        ]);
    }
}
