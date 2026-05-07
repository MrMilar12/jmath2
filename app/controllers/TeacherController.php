<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Quiz;

/**
 * Teacher/Admin Controller
 */
class TeacherController extends Controller
{
    private User $userModel;
    private Lesson $lessonModel;
    private Quiz $quizModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User($this->db);
        $this->lessonModel = new Lesson($this->db);
        $this->quizModel = new Quiz($this->db);
    }

    /**
     * Show teacher dashboard
     */
    public function dashboard()
    {
        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        // Get overview stats
        $query = "SELECT 
                    COUNT(DISTINCT CASE WHEN u.role = 'student' THEN u.id END) as total_students,
                    COUNT(DISTINCT l.id) as total_lessons,
                    COUNT(DISTINCT qa.id) as total_attempts,
                    AVG(qa.percentage) as average_score
                  FROM users u
                  LEFT JOIN lessons l ON TRUE
                  LEFT JOIN quiz_attempts qa ON qa.completed_at IS NOT NULL";
        
        $stats = $this->db->query($query)->fetch();

        // Get student performance
        $studentQuery = "SELECT 
                          u.id,
                          u.display_name,
                          u.email,
                          u.student_id,
                          u.xp,
                          u.level_name,
                          COUNT(DISTINCT qa.id) as attempts,
                          AVG(qa.percentage) as average_score,
                          MAX(qa.percentage) as best_score
                        FROM users u
                        LEFT JOIN quiz_attempts qa ON u.id = qa.student_id AND qa.completed_at IS NOT NULL
                        WHERE u.role = 'student'
                        GROUP BY u.id
                        ORDER BY u.xp DESC";
        
        $students = $this->db->query($studentQuery)->fetchAll();

        // Get recent quiz attempts
        $recentQuery = "SELECT 
                          qa.*,
                          u.display_name,
                          l.title as lesson_title,
                          qa.percentage as score_percentage
                        FROM quiz_attempts qa
                        JOIN users u ON qa.user_id = u.id
                        JOIN lessons l ON qa.lesson_id = l.id
                        WHERE qa.completed_at IS NOT NULL
                        ORDER BY qa.completed_at DESC
                        LIMIT 10";
        
        $recentAttempts = $this->db->query($recentQuery)->fetchAll();

        $this->render('teacher/dashboard', [
            'title' => 'Teacher Dashboard - jmath2',
            'stats' => $stats,
            'students' => $students,
            'recentAttempts' => $recentAttempts
        ]);
    }

    /**
     * Show lessons management
     */
    public function manageLessons()
    {
        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        $lessons = $this->lessonModel->getAllLessons();

        // Get stats for each lesson
        foreach ($lessons as &$lesson) {
            $lesson['stats'] = $this->lessonModel->getLessonStats($lesson['id']);
        }

        $this->render('teacher/lessons', [
            'title' => 'Manage Lessons - jmath2',
            'lessons' => $lessons
        ]);
    }

    /**
     * Show create lesson form
     */
    public function createLessonForm()
    {
        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        // Get modules
        $query = "SELECT m.*, q.title as quarter_title FROM modules m JOIN quarters q ON m.quarter_id = q.id ORDER BY q.id, m.sort_order";
        $modules = $this->db->query($query)->fetchAll();

        $this->render('teacher/create-lesson', [
            'title' => 'Create Lesson - jmath2',
            'modules' => $modules
        ]);
    }

    /**
     * Create lesson
     */
    public function createLesson()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        $data = [
            'module_id' => $_POST['module_id'] ?? 0,
            'title' => $_POST['title'] ?? '',
            'summary' => $_POST['summary'] ?? '',
            'intro_html' => $_POST['intro_html'] ?? '',
            'examples_html' => $_POST['examples_html'] ?? '',
            'practice_html' => $_POST['practice_html'] ?? '',
            'xp_reward' => $_POST['xp_reward'] ?? 10,
            'is_published' => isset($_POST['is_published'])
        ];

        if (empty($data['title']) || empty($data['module_id'])) {
            return $this->json([
                'success' => false,
                'error' => 'Title and module are required'
            ]);
        }

        $lessonId = $this->lessonModel->create($data);

        if ($lessonId) {
            return $this->json([
                'success' => true,
                'lesson_id' => $lessonId,
                'message' => 'Lesson created successfully'
            ]);
        }

        return $this->json([
            'success' => false,
            'error' => 'Failed to create lesson'
        ]);
    }

    /**
     * Edit lesson
     */
    public function editLesson($lessonId)
    {
        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        $lesson = $this->lessonModel->getById($lessonId);
        
        if (!$lesson) {
            return $this->error(404, 'Lesson not found');
        }

        // Get modules
        $query = "SELECT m.*, q.title as quarter_title FROM modules m JOIN quarters q ON m.quarter_id = q.id ORDER BY q.id, m.sort_order";
        $modules = $this->db->query($query)->fetchAll();

        $this->render('teacher/edit-lesson', [
            'title' => 'Edit Lesson - jmath2',
            'lesson' => $lesson,
            'modules' => $modules
        ]);
    }

    /**
     * Update lesson
     */
    public function updateLesson($lessonId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        $data = [
            'title' => $_POST['title'] ?? '',
            'summary' => $_POST['summary'] ?? '',
            'intro_html' => $_POST['intro_html'] ?? '',
            'examples_html' => $_POST['examples_html'] ?? '',
            'practice_html' => $_POST['practice_html'] ?? '',
            'xp_reward' => $_POST['xp_reward'] ?? 10,
            'is_published' => isset($_POST['is_published'])
        ];

        if ($this->lessonModel->update($lessonId, $data)) {
            return $this->json(['success' => true, 'message' => 'Lesson updated']);
        }

        return $this->json(['success' => false, 'error' => 'Failed to update lesson']);
    }

    /**
     * Delete lesson
     */
    public function deleteLesson($lessonId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        if ($this->lessonModel->delete($lessonId)) {
            return $this->json(['success' => true, 'message' => 'Lesson deleted']);
        }

        return $this->json(['success' => false, 'error' => 'Failed to delete lesson']);
    }

    /**
     * Show student analytics
     */
    public function studentAnalytics($studentId)
    {
        if (!$this->isTeacherOrAdmin()) {
            return $this->error(403, 'Access denied');
        }

        $student = $this->userModel->findById($studentId);
        
        if (!$student || $student['role'] !== 'student') {
            return $this->error(404, 'Student not found');
        }

        // Get student stats
        $stats = $this->userModel->getUserStats($studentId);

        // Get recent attempts
        $attemptsQuery = "SELECT 
                            qa.*,
                            l.title as lesson_title,
                            qa.percentage as score_percentage
                          FROM quiz_attempts qa
                          JOIN lessons l ON qa.lesson_id = l.id
                          WHERE qa.user_id = ?
                          ORDER BY qa.completed_at DESC
                          LIMIT 20";
        
        $attempts = $this->db->query($attemptsQuery, [$studentId])->fetchAll();

        // Get lesson progress
        $lessonsQuery = "SELECT 
                          l.title,
                          slp.is_completed,
                          slp.progress_percentage,
                          slp.xp_earned
                        FROM student_lesson_progress slp
                        JOIN lessons l ON slp.lesson_id = l.id
                        WHERE slp.student_id = ?
                        ORDER BY l.created_at DESC";
        
        $lessonsProgress = $this->db->query($lessonsQuery, [$studentId])->fetchAll();

        $this->render('teacher/student-analytics', [
            'title' => 'Student Analytics - jmath2',
            'student' => $student,
            'stats' => $stats,
            'attempts' => $attempts,
            'lessonsProgress' => $lessonsProgress
        ]);
    }

    /**
     * Export student data
     */
    public function exportStudentData()
    {
        if (!$this->isAdmin()) {
            return $this->error(403, 'Access denied');
        }

        $query = "SELECT 
                    u.id,
                    u.display_name,
                    u.email,
                    u.student_id,
                    u.xp,
                    u.level_name,
                    COUNT(DISTINCT slp.lesson_id) as lessons_completed,
                    COUNT(DISTINCT qa.id) as quizzes_taken,
                    AVG(qa.percentage) as average_score
                  FROM users u
                  LEFT JOIN student_lesson_progress slp ON u.id = slp.student_id AND slp.is_completed = TRUE
                  LEFT JOIN quiz_attempts qa ON u.id = qa.user_id AND qa.completed_at IS NOT NULL
                  WHERE u.role = 'student'
                  GROUP BY u.id";
        
        $data = $this->db->query($query)->fetchAll();

        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="student-data.csv"');

        $output = fopen('php://output', 'w');
        
        // Write header
        fputcsv($output, ['ID', 'Name', 'Email', 'Student ID', 'XP', 'Level', 'Lessons Completed', 'Quizzes Taken', 'Average Score']);

        // Write data
        foreach ($data as $row) {
            fputcsv($output, [
                $row['id'],
                $row['display_name'],
                $row['email'],
                $row['student_id'],
                $row['xp'],
                $row['level_name'],
                $row['lessons_completed'],
                $row['quizzes_taken'],
                round($row['average_score'], 2)
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Check if user is teacher or admin
     */
    private function isTeacherOrAdmin(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['role'] ?? null;
        return in_array($role, ['teacher', 'admin']);
    }

    /**
     * Check if user is admin
     */
    private function isAdmin(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return ($_SESSION['role'] ?? null) === 'admin';
    }
}
