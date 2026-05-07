<?php

namespace App\Models;

use App\Database\Database;

/**
 * Progress Model
 * Tracks student progress through lessons and modules
 */
class Progress
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Start lesson
     */
    public function startLesson(int $userId, int $lessonId): bool
    {
        $query = "INSERT INTO student_lesson_progress (student_id, lesson_id, started_at) 
                  VALUES (?, ?, NOW())
                  ON DUPLICATE KEY UPDATE started_at = IF(started_at IS NULL, NOW(), started_at)";
        return $this->db->execute($query, [$userId, $lessonId]);
    }

    /**
     * Complete lesson
     */
    public function completeLesson(int $userId, int $lessonId, int $xpEarned = 0): bool
    {
        $query = "UPDATE student_lesson_progress 
                 SET is_completed = TRUE, completed_at = NOW(), xp_earned = ?, progress_percentage = 100 
                 WHERE student_id = ? AND lesson_id = ?";
        return $this->db->execute($query, [$xpEarned, $userId, $lessonId]);
    }

    /**
     * Update lesson progress
     */
    public function updateLessonProgress(int $userId, int $lessonId, int $progressPercentage): bool
    {
        $query = "UPDATE student_lesson_progress 
                 SET progress_percentage = ? 
                 WHERE student_id = ? AND lesson_id = ?";
        return $this->db->execute($query, [$progressPercentage, $userId, $lessonId]);
    }

    /**
     * Get lesson progress
     */
    public function getLessonProgress(int $userId, int $lessonId): ?array
    {
        $query = "SELECT * FROM student_lesson_progress WHERE student_id = ? AND lesson_id = ?";
        return $this->db->query($query, [$userId, $lessonId])->fetch();
    }

    /**
     * Get all lessons progress for user
     */
    public function getUserLessonsProgress(int $userId): array
    {
        $query = "SELECT 
                    slp.*,
                    l.title,
                    l.slug,
                    m.title as module_title,
                    m.id as module_id
                  FROM student_lesson_progress slp
                  JOIN lessons l ON slp.lesson_id = l.id
                  JOIN modules m ON l.module_id = m.id
                  WHERE slp.student_id = ?
                  ORDER BY m.id, l.sort_order";
        
        return $this->db->query($query, [$userId])->fetchAll();
    }

    /**
     * Start module
     */
    public function startModule(int $userId, int $moduleId): bool
    {
        $query = "INSERT INTO module_progress (student_id, module_id, started_at) 
                  VALUES (?, ?, NOW())
                  ON DUPLICATE KEY UPDATE started_at = IF(started_at IS NULL, NOW(), started_at)";
        return $this->db->execute($query, [$userId, $moduleId]);
    }

    /**
     * Update module progress
     */
    public function updateModuleProgress(int $userId, int $moduleId): void
    {
        // Count completed lessons in module
        $query = "SELECT 
                    COUNT(l.id) as total_lessons,
                    SUM(CASE WHEN slp.is_completed = TRUE THEN 1 ELSE 0 END) as completed_lessons
                  FROM lessons l
                  LEFT JOIN student_lesson_progress slp ON l.id = slp.lesson_id AND slp.student_id = ?
                  WHERE l.module_id = ?";
        
        $result = $this->db->query($query, [$userId, $moduleId])->fetch();
        
        $totalLessons = $result['total_lessons'] ?? 0;
        $completedLessons = $result['completed_lessons'] ?? 0;
        $percentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        $isCompleted = $completedLessons === $totalLessons && $totalLessons > 0;

        $updateQuery = "UPDATE module_progress 
                       SET completed_lessons = ?, total_lessons = ?, progress_percentage = ?, is_completed = ?,
                           completed_at = IF(is_completed = TRUE AND ? = TRUE, completed_at, IF(? = TRUE, NOW(), completed_at))
                       WHERE student_id = ? AND module_id = ?";
        
        $this->db->execute($updateQuery, [
            $completedLessons,
            $totalLessons,
            $percentage,
            $isCompleted ? 1 : 0,
            $isCompleted ? 1 : 0,
            $isCompleted ? 1 : 0,
            $userId,
            $moduleId
        ]);
    }

    /**
     * Get module progress
     */
    public function getModuleProgress(int $userId, int $moduleId): ?array
    {
        $query = "SELECT mp.*, m.title FROM module_progress mp 
                  JOIN modules m ON mp.module_id = m.id 
                  WHERE mp.student_id = ? AND mp.module_id = ?";
        return $this->db->query($query, [$userId, $moduleId])->fetch();
    }

    /**
     * Get all modules progress for user
     */
    public function getUserModulesProgress(int $userId): array
    {
        $query = "SELECT mp.*, m.title FROM module_progress mp 
                  JOIN modules m ON mp.module_id = m.id 
                  WHERE mp.student_id = ?
                  ORDER BY m.quarter_id, m.sort_order";
        return $this->db->query($query, [$userId])->fetchAll();
    }

    /**
     * Get overall progress percentage for user
     */
    public function getOverallProgress(int $userId): float
    {
        $query = "SELECT AVG(progress_percentage) as overall_progress FROM module_progress WHERE student_id = ?";
        $result = $this->db->query($query, [$userId])->fetch();
        return floatval($result['overall_progress'] ?? 0);
    }

    /**
     * Get completed lessons for user
     */
    public function getCompletedLessons(int $userId): int
    {
        $query = "SELECT COUNT(*) as count FROM student_lesson_progress WHERE student_id = ? AND is_completed = TRUE";
        $result = $this->db->query($query, [$userId])->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Get total lessons available
     */
    public function getTotalLessons(): int
    {
        $query = "SELECT COUNT(*) as count FROM lessons WHERE is_published = TRUE";
        $result = $this->db->query($query)->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Get user's total study time
     */
    public function getStudyTime(int $userId): int
    {
        // Calculate total time spent in lessons (in minutes)
        // This assumes we have timestamps for started_at and completed_at
        $query = "SELECT SUM(FLOOR(TIMESTAMPDIFF(MINUTE, started_at, completed_at))) as total_minutes 
                  FROM student_lesson_progress 
                  WHERE student_id = ? AND completed_at IS NOT NULL";
        
        $result = $this->db->query($query, [$userId])->fetch();
        return intval($result['total_minutes'] ?? 0);
    }

    /**
     * Get time spent on specific lesson
     */
    public function getLessonStudyTime(int $userId, int $lessonId): int
    {
        $query = "SELECT FLOOR(TIMESTAMPDIFF(MINUTE, started_at, COALESCE(completed_at, NOW()))) as minutes 
                  FROM student_lesson_progress 
                  WHERE student_id = ? AND lesson_id = ?";
        
        $result = $this->db->query($query, [$userId, $lessonId])->fetch();
        return intval($result['minutes'] ?? 0);
    }

    /**
     * Get class progress overview
     */
    public function getClassProgress(): array
    {
        $query = "SELECT 
                    AVG(mp.progress_percentage) as class_average,
                    MAX(mp.progress_percentage) as highest_progress,
                    MIN(mp.progress_percentage) as lowest_progress,
                    COUNT(DISTINCT mp.student_id) as students_count
                  FROM module_progress mp";
        
        return $this->db->query($query)->fetch();
    }

    /**
     * Get students needing intervention (low progress)
     */
    public function getStudentsNeedingIntervention(int $threshold = 30): array
    {
        $query = "SELECT 
                    u.id,
                    u.display_name,
                    u.email,
                    AVG(mp.progress_percentage) as average_progress
                  FROM users u
                  LEFT JOIN module_progress mp ON u.id = mp.student_id
                  WHERE u.role = 'student' AND u.is_active = TRUE
                  GROUP BY u.id
                  HAVING AVG(COALESCE(mp.progress_percentage, 0)) < ?
                  ORDER BY average_progress ASC";
        
        return $this->db->query($query, [$threshold])->fetchAll();
    }

    /**
     * Get most difficult lessons
     */
    public function getMostDifficultLessons(int $limit = 5): array
    {
        $query = "SELECT 
                    l.id,
                    l.title,
                    AVG(qa.percentage) as average_score,
                    COUNT(qa.id) as attempt_count
                  FROM lessons l
                  LEFT JOIN quiz_attempts qa ON l.id = qa.lesson_id AND qa.completed_at IS NOT NULL
                  WHERE l.is_published = TRUE
                  GROUP BY l.id
                  ORDER BY average_score ASC
                  LIMIT ?";
        
        return $this->db->query($query, [$limit])->fetchAll();
    }

    /**
     * Get progress timeline for user
     */
    public function getUserProgressTimeline(int $userId, int $days = 30): array
    {
        $query = "SELECT 
                    DATE(completed_at) as date,
                    COUNT(*) as lessons_completed,
                    SUM(xp_earned) as xp_earned
                  FROM student_lesson_progress
                  WHERE student_id = ? AND completed_at IS NOT NULL AND completed_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(completed_at)
                  ORDER BY date DESC";
        
        return $this->db->query($query, [$userId, $days])->fetchAll();
    }
}
