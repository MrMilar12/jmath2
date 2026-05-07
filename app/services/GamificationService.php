<?php

namespace App\Services;

use App\Database\Database;
use App\Models\User;

/**
 * Gamification Service
 * Handles badges, streaks, levels, and rewards
 */
class GamificationService
{
    private Database $db;
    private User $userModel;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    /**
     * Award XP to user
     */
    public function awardXP(int $userId, int $xpAmount): void
    {
        // Update XP
        $this->userModel->updateXP($userId, $xpAmount);

        // Check for level up
        $this->checkLevelUp($userId);

        // Check for badge eligibility
        $this->checkBadges($userId);

        // Update daily streak
        $this->updateStreak($userId);

        // Update leaderboard
        $this->updateLeaderboard($userId);
    }

    /**
     * Check if user should level up
     */
    private function checkLevelUp(int $userId): void
    {
        $user = $this->userModel->findById($userId);
        $currentXP = $user['xp'];

        $levelMap = [
            'Beginner' => 0,
            'Novice' => 100,
            'Intermediate' => 300,
            'Advanced' => 600,
            'Expert' => 1000
        ];

        $newLevel = 'Beginner';
        foreach ($levelMap as $level => $xpRequired) {
            if ($currentXP >= $xpRequired) {
                $newLevel = $level;
            }
        }

        // Update level if different
        if ($newLevel !== $user['level_name']) {
            $this->userModel->updateLevel($userId, $newLevel);

            // Create notification
            $this->createNotification(
                $userId,
                'level_up',
                'Level Up!',
                "Congratulations! You've reached the $newLevel level!"
            );
        }
    }

    /**
     * Check and award badges
     */
    private function checkBadges(int $userId): void
    {
        // Get all badges
        $query = "SELECT * FROM badges";
        $badges = $this->db->query($query)->fetchAll();

        foreach ($badges as $badge) {
            // Skip if already earned
            if ($this->userModel->hasBadge($userId, $badge['id'])) {
                continue;
            }

            $shouldAward = false;

            if ($badge['requirement_type'] === 'xp') {
                $user = $this->userModel->findById($userId);
                $shouldAward = $user['xp'] >= $badge['required_xp'];
            } elseif ($badge['requirement_type'] === 'completion') {
                $completedQuery = "SELECT COUNT(*) as count FROM student_lesson_progress 
                                  WHERE student_id = ? AND is_completed = TRUE";
                $result = $this->db->query($completedQuery, [$userId])->fetch();
                $shouldAward = $result['count'] >= $badge['requirement_value'];
            } elseif ($badge['requirement_type'] === 'score') {
                $scoreQuery = "SELECT MAX(percentage) as max_score FROM quiz_attempts 
                              WHERE user_id = ? AND completed_at IS NOT NULL";
                $result = $this->db->query($scoreQuery, [$userId])->fetch();
                $shouldAward = $result['max_score'] >= $badge['requirement_value'];
            } elseif ($badge['requirement_type'] === 'streak') {
                $streakQuery = "SELECT current_streak FROM student_daily_streaks WHERE student_id = ?";
                $result = $this->db->query($streakQuery, [$userId])->fetch();
                $shouldAward = $result['current_streak'] >= $badge['requirement_value'];
            }

            if ($shouldAward) {
                $this->userModel->awardBadge($userId, $badge['id']);

                // Create notification
                $this->createNotification(
                    $userId,
                    'badge_earned',
                    'Badge Earned!',
                    "You've earned the {$badge['title']} badge!"
                );
            }
        }
    }

    /**
     * Update daily streak
     */
    private function updateStreak(int $userId): void
    {
        $query = "SELECT * FROM student_daily_streaks WHERE student_id = ?";
        $streak = $this->db->query($query, [$userId])->fetch();

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if (!$streak) {
            // Create new streak record
            $insertQuery = "INSERT INTO student_daily_streaks (student_id, current_streak, longest_streak, last_activity_date) 
                           VALUES (?, 1, 1, ?)";
            $this->db->execute($insertQuery, [$userId, $today]);
        } else {
            $lastDate = date('Y-m-d', strtotime($streak['last_activity_date']));

            if ($lastDate === $today) {
                // Already updated today, skip
                return;
            }

            if ($lastDate === $yesterday) {
                // Streak continues
                $newStreak = $streak['current_streak'] + 1;
                $newLongestStreak = max($newStreak, $streak['longest_streak']);

                $updateQuery = "UPDATE student_daily_streaks SET current_streak = ?, longest_streak = ?, last_activity_date = ? 
                               WHERE student_id = ?";
                $this->db->execute($updateQuery, [$newStreak, $newLongestStreak, $today, $userId]);
            } else {
                // Streak broken, reset to 1
                $updateQuery = "UPDATE student_daily_streaks SET current_streak = 1, last_activity_date = ? 
                               WHERE student_id = ?";
                $this->db->execute($updateQuery, [$today, $userId]);
            }
        }
    }

    /**
     * Update leaderboard
     */
    private function updateLeaderboard(int $userId): void
    {
        $user = $this->userModel->findById($userId);

        // Get current rank
        $rankQuery = "SELECT COUNT(*) + 1 as rank FROM users WHERE xp > ? AND role = 'student'";
        $rankResult = $this->db->query($rankQuery, [$user['xp']])->fetch();
        $rank = $rankResult['rank'] ?? 0;

        // Check if leaderboard entry exists
        $existsQuery = "SELECT id FROM leaderboard WHERE user_id = ?";
        $exists = $this->db->query($existsQuery, [$userId])->fetch();

        if ($exists) {
            $updateQuery = "UPDATE leaderboard SET total_xp = ?, rank = ?, updated_at = NOW() WHERE user_id = ?";
            $this->db->execute($updateQuery, [$user['xp'], $rank, $userId]);
        } else {
            $insertQuery = "INSERT INTO leaderboard (user_id, total_xp, rank) VALUES (?, ?, ?)";
            $this->db->execute($insertQuery, [$userId, $user['xp'], $rank]);
        }
    }

    /**
     * Create notification
     */
    private function createNotification(int $userId, string $type, string $title, string $message): void
    {
        $query = "INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)";
        $this->db->execute($query, [$userId, $type, $title, $message]);
    }

    /**
     * Get user total XP
     */
    public function getUserTotalXP(int $userId): int
    {
        $user = $this->userModel->findById($userId);
        return $user['xp'] ?? 0;
    }

    /**
     * Get user level
     */
    public function getUserLevel(int $userId): string
    {
        $user = $this->userModel->findById($userId);
        return $user['level_name'] ?? 'Beginner';
    }

    /**
     * Get user streak
     */
    public function getUserStreak(int $userId): ?array
    {
        $query = "SELECT * FROM student_daily_streaks WHERE student_id = ?";
        return $this->db->query($query, [$userId])->fetch();
    }

    /**
     * Get user leaderboard position
     */
    public function getUserLeaderboardPosition(int $userId): ?array
    {
        $query = "SELECT * FROM leaderboard WHERE user_id = ?";
        return $this->db->query($query, [$userId])->fetch();
    }

    /**
     * Get top 10 leaderboard
     */
    public function getTopLeaderboard(int $limit = 10): array
    {
        $query = "SELECT u.id, u.display_name, u.xp, u.level_name, l.rank 
                  FROM users u
                  LEFT JOIN leaderboard l ON u.id = l.user_id
                  WHERE u.role = 'student' AND u.is_active = TRUE
                  ORDER BY u.xp DESC
                  LIMIT ?";
        return $this->db->query($query, [$limit])->fetchAll();
    }

    /**
     * Reset weekly XP (called via cron)
     */
    public function resetWeeklyXP(): void
    {
        $query = "UPDATE leaderboard SET weekly_xp = 0, weekly_rank = NULL";
        $this->db->execute($query);
    }

    /**
     * Get achievement progress for user
     */
    public function getAchievementProgress(int $userId): array
    {
        $user = $this->userModel->findById($userId);
        
        // Count completed lessons
        $lessonsQuery = "SELECT COUNT(*) as count FROM student_lesson_progress WHERE student_id = ? AND is_completed = TRUE";
        $lessonsResult = $this->db->query($lessonsQuery, [$userId])->fetch();

        // Count completed quizzes
        $quizzesQuery = "SELECT COUNT(*) as count FROM quiz_attempts WHERE user_id = ? AND completed_at IS NOT NULL";
        $quizzesResult = $this->db->query($quizzesQuery, [$userId])->fetch();

        // Count badges
        $badgesQuery = "SELECT COUNT(*) as count FROM student_badges WHERE student_id = ?";
        $badgesResult = $this->db->query($badgesQuery, [$userId])->fetch();

        return [
            'xp' => $user['xp'],
            'level' => $user['level_name'],
            'lessons_completed' => $lessonsResult['count'] ?? 0,
            'quizzes_completed' => $quizzesResult['count'] ?? 0,
            'badges_earned' => $badgesResult['count'] ?? 0
        ];
    }
}
