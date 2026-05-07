<?php

namespace App\Models;

use App\Database\Database;

/**
 * Quiz Model
 */
class Quiz
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get quiz by lesson ID and type
     */
    public function getQuizByLessonAndType(int $lessonId, string $type): ?array
    {
        $query = "SELECT * FROM quizzes WHERE lesson_id = ? AND quiz_type = ?";
        return $this->db->query($query, [$lessonId, $type])->fetch();
    }

    /**
     * Get questions for quiz
     */
    public function getQuestions(int $quizId, bool $randomize = true): array
    {
        $order = $randomize ? 'RAND()' : 'order_number';
        $query = "SELECT * FROM questions WHERE quiz_id = ? ORDER BY $order";
        $questions = $this->db->query($query, [$quizId])->fetchAll();

        // Fetch choices for each question
        foreach ($questions as &$question) {
            if ($question['question_type'] === 'multiple_choice') {
                $choicesQuery = "SELECT * FROM question_choices WHERE question_id = ? ORDER BY order_number";
                $question['choices'] = $this->db->query($choicesQuery, [$question['id']])->fetchAll();
                
                if ($question['randomize_choices']) {
                    shuffle($question['choices']);
                }
            }
        }

        return $questions;
    }

    /**
     * Create quiz attempt
     */
    public function createAttempt(int $userId, int $quizId): int
    {
        $query = "INSERT INTO quiz_attempts (user_id, quiz_id, started_at) VALUES (?, ?, NOW())";
        $this->db->execute($query, [$userId, $quizId]);
        return $this->db->lastInsertId();
    }

    /**
     * Save question response
     */
    public function saveResponse(int $attemptId, int $questionId, string $userResponse, bool $isCorrect, int $pointsEarned): bool
    {
        $query = "INSERT INTO question_responses (quiz_attempt_id, question_id, user_response, is_correct, points_earned, answered_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        return $this->db->execute($query, [$attemptId, $questionId, $userResponse, $isCorrect ? 1 : 0, $pointsEarned]);
    }

    /**
     * Complete attempt
     */
    public function completeAttempt(int $attemptId, int $score, int $totalPoints, float $percentage, bool $passed): bool
    {
        $query = "UPDATE quiz_attempts SET score = ?, total_points = ?, percentage = ?, passed = ?, completed_at = NOW() 
                  WHERE id = ?";
        return $this->db->execute($query, [$score, $totalPoints, $percentage, $passed ? 1 : 0, $attemptId]);
    }

    /**
     * Get attempt details
     */
    public function getAttempt(int $attemptId): ?array
    {
        $query = "SELECT * FROM quiz_attempts WHERE id = ?";
        return $this->db->query($query, [$attemptId])->fetch();
    }

    /**
     * Get all attempts for user
     */
    public function getUserAttempts(int $userId, int $limit = 10): array
    {
        $query = "SELECT qa.*, q.title as quiz_title, l.title as lesson_title 
                  FROM quiz_attempts qa
                  JOIN quizzes q ON qa.quiz_id = q.id
                  JOIN lessons l ON q.lesson_id = l.id
                  WHERE qa.user_id = ?
                  ORDER BY qa.completed_at DESC
                  LIMIT ?";
        return $this->db->query($query, [$userId, $limit])->fetchAll();
    }

    /**
     * Get average score for user
     */
    public function getUserAverageScore(int $userId): float
    {
        $query = "SELECT AVG(percentage) as avg_score FROM quiz_attempts WHERE user_id = ? AND completed_at IS NOT NULL";
        $result = $this->db->query($query, [$userId])->fetch();
        return floatval($result['avg_score'] ?? 0);
    }

    /**
     * Get best score for a quiz
     */
    public function getUserBestScore(int $userId, int $quizId): ?array
    {
        $query = "SELECT * FROM quiz_attempts WHERE user_id = ? AND quiz_id = ? ORDER BY percentage DESC LIMIT 1";
        return $this->db->query($query, [$userId, $quizId])->fetch();
    }

    /**
     * Count completed quizzes by user
     */
    public function countCompletedQuizzes(int $userId): int
    {
        $query = "SELECT COUNT(*) as count FROM quiz_attempts WHERE user_id = ? AND completed_at IS NOT NULL";
        $result = $this->db->query($query, [$userId])->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Get question details
     */
    public function getQuestion(int $questionId): ?array
    {
        $query = "SELECT * FROM questions WHERE id = ?";
        return $this->db->query($query, [$questionId])->fetch();
    }

    /**
     * Check if answer is correct
     */
    public function checkAnswer(int $questionId, string $userAnswer): bool
    {
        $question = $this->getQuestion($questionId);
        
        if (!$question) {
            return false;
        }

        if ($question['question_type'] === 'multiple_choice') {
            return strtolower($userAnswer) === strtolower($question['correct_option']);
        } elseif ($question['question_type'] === 'fill_blank') {
            return strtolower(trim($userAnswer)) === strtolower(trim($question['correct_text']));
        }

        return false;
    }

    /**
     * Get points for question
     */
    public function getQuestionPoints(int $questionId): int
    {
        $question = $this->getQuestion($questionId);
        return $question['points'] ?? 1;
    }
}
