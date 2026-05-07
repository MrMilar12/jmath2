<?php

namespace App\Models;

use App\Database\Database;

/**
 * User Model
 */
class User
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT id, role, email, student_id, username, display_name, xp, level_name, created_at, is_active, last_login FROM users WHERE id = ?";
        return $this->db->query($query, [$id])->fetch();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $query = "SELECT * FROM users WHERE email = ?";
        return $this->db->query($query, [$email])->fetch();
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $query = "SELECT * FROM users WHERE username = ?";
        return $this->db->query($query, [$username])->fetch();
    }

    /**
     * Find user by email or username
     */
    public function findByEmailOrUsername(string $emailOrUsername): ?array
    {
        $query = "SELECT * FROM users WHERE email = ? OR username = ?";
        return $this->db->query($query, [$emailOrUsername, $emailOrUsername])->fetch();
    }

    /**
     * Create new user
     */
    public function create(array $data): ?int
    {
        $query = "INSERT INTO users (role, email, student_id, username, password_hash, display_name, xp, level_name, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, 0, 'Beginner', TRUE)";
        
        $result = $this->db->execute($query, [
            $data['role'] ?? 'student',
            $data['email'],
            $data['student_id'] ?? null,
            $data['username'] ?? null,
            $data['password_hash'],
            $data['display_name']
        ]);

        return $result ? $this->db->lastInsertId() : null;
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['email', 'username', 'display_name', 'xp', 'level_name', 'is_active'])) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        
        return $this->db->execute($query, $params);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        return $this->db->execute($query, [$userId]);
    }

    /**
     * Update user XP
     */
    public function updateXP(int $userId, int $xpToAdd): bool
    {
        $query = "UPDATE users SET xp = xp + ? WHERE id = ?";
        return $this->db->execute($query, [$xpToAdd, $userId]);
    }

    /**
     * Update user level
     */
    public function updateLevel(int $userId, string $levelName): bool
    {
        $query = "UPDATE users SET level_name = ? WHERE id = ?";
        return $this->db->execute($query, [$levelName, $userId]);
    }

    /**
     * Get user statistics
     */
    public function getUserStats(int $userId): ?array
    {
        $query = "SELECT 
                    u.id,
                    u.display_name,
                    u.xp,
                    u.level_name,
                    COUNT(DISTINCT slp.lesson_id) as lessons_completed,
                    COUNT(DISTINCT qa.id) as quizzes_taken,
                    AVG(qa.percentage) as average_score,
                    COUNT(DISTINCT sb.badge_id) as badges_earned
                  FROM users u
                  LEFT JOIN student_lesson_progress slp ON u.id = slp.student_id AND slp.is_completed = TRUE
                  LEFT JOIN quiz_attempts qa ON u.id = qa.student_id
                  LEFT JOIN student_badges sb ON u.id = sb.student_id
                  WHERE u.id = ?
                  GROUP BY u.id";
        
        return $this->db->query($query, [$userId])->fetch();
    }

    /**
     * Get leaderboard ranking for user
     */
    public function getLeaderboardRank(int $userId): ?array
    {
        $query = "SELECT 
                    l.rank,
                    l.total_xp,
                    u.display_name,
                    u.level_name
                  FROM leaderboard l
                  JOIN users u ON l.user_id = u.id
                  WHERE l.user_id = ?";
        
        return $this->db->query($query, [$userId])->fetch();
    }

    /**
     * Check if user has earned a badge
     */
    public function hasBadge(int $userId, int $badgeId): bool
    {
        $query = "SELECT id FROM student_badges WHERE student_id = ? AND badge_id = ?";
        return $this->db->query($query, [$userId, $badgeId])->fetch() !== null;
    }

    /**
     * Award badge to user
     */
    public function awardBadge(int $userId, int $badgeId): bool
    {
        if ($this->hasBadge($userId, $badgeId)) {
            return true; // Already has badge
        }

        $query = "INSERT INTO student_badges (student_id, badge_id) VALUES (?, ?)";
        return $this->db->execute($query, [$userId, $badgeId]);
    }

    /**
     * Get user's badges
     */
    public function getBadges(int $userId): array
    {
        $query = "SELECT b.* FROM badges b
                  JOIN student_badges sb ON b.id = sb.badge_id
                  WHERE sb.student_id = ?
                  ORDER BY sb.awarded_at DESC";
        
        return $this->db->query($query, [$userId])->fetchAll();
    }

    /**
     * Deactivate user account
     */
    public function deactivate(int $userId): bool
    {
        $query = "UPDATE users SET is_active = FALSE WHERE id = ?";
        return $this->db->execute($query, [$userId]);
    }

    /**
     * Activate user account
     */
    public function activate(int $userId): bool
    {
        $query = "UPDATE users SET is_active = TRUE WHERE id = ?";
        return $this->db->execute($query, [$userId]);
    }

    /**
     * Get all students (pagination)
     */
    public function getStudents(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $query = "SELECT id, email, student_id, display_name, xp, level_name, created_at, is_active 
                  FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        return $this->db->query($query, [$limit, $offset])->fetchAll();
    }

    /**
     * Count total students
     */
    public function countStudents(): int
    {
        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
        $result = $this->db->query($query)->fetch();
        return $result['count'] ?? 0;
    }
}
