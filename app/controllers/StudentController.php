<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Models\User;

/**
 * Student Dashboard Controller
 */
class StudentController extends Controller
{
    private User $userModel;
    private AuthMiddleware $authMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User($this->db);
        $this->authMiddleware = new AuthMiddleware($GLOBALS['auth'] ?? null);
    }

    /**
     * Show student dashboard
     */
    public function dashboard()
    {
        // Require student authentication
        if (!$this->isStudent()) {
            return $this->error(403, 'Access denied');
        }

        $userId = $this->getCurrentUserId();
        $user = $this->userModel->findById($userId);
        $stats = $this->userModel->getUserStats($userId);
        $leaderboardRank = $this->userModel->getLeaderboardRank($userId);
        $badges = $this->userModel->getBadges($userId);

        // Get lessons progress
        $query = "SELECT 
                    l.id,
                    l.title,
                    l.slug,
                    m.title as module_title,
                    slp.is_completed,
                    slp.progress_percentage,
                    slp.xp_earned
                  FROM lessons l
                  JOIN modules m ON l.module_id = m.id
                  LEFT JOIN student_lesson_progress slp ON l.id = slp.lesson_id AND slp.student_id = ?
                  ORDER BY m.id, l.sort_order";
        
        $lessons = $this->db->query($query, [$userId])->fetchAll();

        // Get notifications
        $notifQuery = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
        $notifications = $this->db->query($notifQuery, [$userId])->fetchAll();

        $this->render('student/dashboard', [
            'title' => 'Dashboard - jmath2',
            'user' => $user,
            'stats' => $stats,
            'leaderboardRank' => $leaderboardRank,
            'badges' => $badges,
            'lessons' => $lessons,
            'notifications' => $notifications
        ]);
    }

    /**
     * Show lesson
     */
    public function viewLesson($slug)
    {
        if (!$this->isStudent()) {
            return $this->error(403, 'Access denied');
        }

        $userId = $this->getCurrentUserId();

        $query = "SELECT 
                    l.*,
                    m.title as module_title,
                    q.title as quarter_title,
                    slp.is_completed,
                    slp.progress_percentage
                  FROM lessons l
                  JOIN modules m ON l.module_id = m.id
                  JOIN quarters q ON m.quarter_id = q.id
                  LEFT JOIN student_lesson_progress slp ON l.id = slp.lesson_id AND slp.student_id = ?
                  WHERE l.slug = ? AND l.is_published = TRUE";
        
        $lesson = $this->db->query($query, [$userId, $slug])->fetch();

        if (!$lesson) {
            return $this->error(404, 'Lesson not found');
        }

        // Mark lesson as started
        $this->startLesson($userId, $lesson['id']);

        $this->render('student/lesson', [
            'title' => $lesson['title'] . ' - jmath2',
            'lesson' => $lesson
        ]);
    }

    /**
     * Show quiz
     */
    public function takeQuiz($slug)
    {
        if (!$this->isStudent()) {
            return $this->error(403, 'Access denied');
        }

        $userId = $this->getCurrentUserId();
        $quizType = $_GET['type'] ?? 'post';

        if (!in_array($quizType, ['pre', 'post', 'practice'])) {
            $quizType = 'post';
        }

        $query = "SELECT l.* FROM lessons l WHERE l.slug = ? AND l.is_published = TRUE";
        $lesson = $this->db->query($query, [$slug])->fetch();

        if (!$lesson) {
            return $this->error(404, 'Lesson not found');
        }

        // Get questions
        $questionsQuery = "SELECT * FROM quiz_questions 
                          WHERE lesson_id = ? AND quiz_type = ? 
                          ORDER BY" . (rand(0, 1) ? ' RAND()' : ' id');
        $questions = $this->db->query($questionsQuery, [$lesson['id'], $quizType])->fetchAll();

        if (empty($questions)) {
            return $this->error(404, 'No questions available');
        }

        // Get choices for each question
        foreach ($questions as &$question) {
            if (in_array($question['question_kind'], ['mcq'])) {
                $choicesQuery = "SELECT * FROM question_choices WHERE question_id = ? ORDER BY order_number";
                $question['choices'] = $this->db->query($choicesQuery, [$question['id']])->fetchAll();
            }
        }

        $this->render('student/quiz', [
            'title' => 'Quiz - jmath2',
            'lesson' => $lesson,
            'quizType' => $quizType,
            'questions' => $questions
        ]);
    }

    /**
     * Submit quiz
     */
    public function submitQuiz()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error(405, 'Method not allowed');
        }

        if (!$this->isStudent()) {
            return $this->error(403, 'Access denied');
        }

        $userId = $this->getCurrentUserId();
        $lessonId = $_POST['lesson_id'] ?? 0;
        $quizType = $_POST['quiz_type'] ?? 'post';

        if (!in_array($quizType, ['pre', 'post', 'practice'])) {
            return $this->json(['success' => false, 'error' => 'Invalid quiz type']);
        }

        // Create quiz attempt
        $attemptQuery = "INSERT INTO quiz_attempts (student_id, lesson_id, quiz_type, started_at) 
                        VALUES (?, ?, ?, NOW())";
        $this->db->execute($attemptQuery, [$userId, $lessonId, $quizType]);
        $attemptId = $this->db->lastInsertId();

        $correct = 0;
        $total = 0;

        // Process answers
        $answers = json_decode($_POST['answers'] ?? '[]', true);
        
        foreach ($answers as $questionId => $userResponse) {
            $total++;
            $questionQuery = "SELECT * FROM quiz_questions WHERE id = ?";
            $question = $this->db->query($questionQuery, [$questionId])->fetch();

            if (!$question) {
                continue;
            }

            $isCorrect = false;
            $pointsEarned = 0;

            // Check answer
            if ($question['question_kind'] === 'mcq') {
                $isCorrect = strtolower($userResponse) === strtolower($question['correct_option']);
            } elseif ($question['question_kind'] === 'fill_blank') {
                $isCorrect = strtolower($userResponse) === strtolower($question['correct_text']);
            }

            if ($isCorrect) {
                $correct++;
                $pointsEarned = $question['points'] ?? 1;
            }

            // Save response
            $responseQuery = "INSERT INTO question_responses 
                            (quiz_attempt_id, question_id, user_response, is_correct, points_earned) 
                            VALUES (?, ?, ?, ?, ?)";
            $this->db->execute($responseQuery, [$attemptId, $questionId, $userResponse, $isCorrect ? 1 : 0, $pointsEarned]);
        }

        // Calculate score
        $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $passed = $percentage >= 70;

        // Update attempt with results
        $updateQuery = "UPDATE quiz_attempts 
                       SET score = ?, total_items = ?, percentage = ?, passed = ?, completed_at = NOW() 
                       WHERE id = ?";
        $this->db->execute($updateQuery, [$correct, $total, $percentage, $passed ? 1 : 0, $attemptId]);

        // Award XP if passed
        if ($passed) {
            $xpReward = 10; // Base XP
            if ($percentage === 100) {
                $xpReward += 5; // Bonus for perfect score
            }
            
            $this->userModel->updateXP($userId, $xpReward);
            
            // Check for badge eligibility
            $this->checkAndAwardBadges($userId);
        }

        return $this->json([
            'success' => true,
            'score' => $percentage,
            'correct' => $correct,
            'total' => $total,
            'passed' => $passed
        ]);
    }

    /**
     * Get leaderboard
     */
    public function leaderboard()
    {
        $query = "SELECT u.id, u.display_name, u.xp, u.level_name, l.rank 
                  FROM users u
                  LEFT JOIN leaderboard l ON u.id = l.user_id
                  WHERE u.role = 'student' AND u.is_active = TRUE
                  ORDER BY u.xp DESC
                  LIMIT 100";
        
        $leaders = $this->db->query($query)->fetchAll();

        $this->render('student/leaderboard', [
            'title' => 'Leaderboard - jmath2',
            'leaders' => $leaders
        ]);
    }

    /**
     * Get progress
     */
    public function progress()
    {
        if (!$this->isStudent()) {
            return $this->error(403, 'Access denied');
        }

        $userId = $this->getCurrentUserId();

        $query = "SELECT 
                    mp.module_id,
                    m.title,
                    mp.progress_percentage,
                    mp.is_completed,
                    COUNT(l.id) as total_lessons,
                    SUM(CASE WHEN slp.is_completed = TRUE THEN 1 ELSE 0 END) as completed_lessons
                  FROM module_progress mp
                  JOIN modules m ON mp.module_id = m.id
                  LEFT JOIN lessons l ON m.id = l.module_id
                  LEFT JOIN student_lesson_progress slp ON l.id = slp.lesson_id AND slp.student_id = ?
                  WHERE mp.student_id = ?
                  GROUP BY mp.module_id, m.title";
        
        $modules = $this->db->query($query, [$userId, $userId])->fetchAll();

        $this->render('student/progress', [
            'title' => 'Progress - jmath2',
            'modules' => $modules
        ]);
    }

    /**
     * Check and award badges
     */
    private function checkAndAwardBadges(int $userId): void
    {
        $user = $this->userModel->findById($userId);

        // Check badges
        $badgesQuery = "SELECT * FROM badges";
        $badges = $this->db->query($badgesQuery)->fetchAll();

        foreach ($badges as $badge) {
            // Skip if already earned
            if ($this->userModel->hasBadge($userId, $badge['id'])) {
                continue;
            }

            $shouldAward = false;

            if ($badge['requirement_type'] === 'xp') {
                $shouldAward = $user['xp'] >= $badge['required_xp'];
            } elseif ($badge['requirement_type'] === 'completion') {
                $completedQuery = "SELECT COUNT(*) as count FROM student_lesson_progress WHERE student_id = ? AND is_completed = TRUE";
                $result = $this->db->query($completedQuery, [$userId])->fetch();
                $shouldAward = $result['count'] >= $badge['requirement_value'];
            }

            if ($shouldAward) {
                $this->userModel->awardBadge($userId, $badge['id']);
            }
        }
    }

    /**
     * Start lesson (mark as started)
     */
    private function startLesson(int $userId, int $lessonId): void
    {
        $query = "INSERT INTO student_lesson_progress (student_id, lesson_id, started_at) 
                  VALUES (?, ?, NOW())
                  ON DUPLICATE KEY UPDATE started_at = IF(started_at IS NULL, NOW(), started_at)";
        $this->db->execute($query, [$userId, $lessonId]);
    }

    /**
     * Check if user is student
     */
    private function isStudent(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['role'] === 'student';
    }

    /**
     * Get current user ID
     */
    private function getCurrentUserId(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? 0;
    }
}
