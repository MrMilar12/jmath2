<?php

namespace App\Models;

use App\Database\Database;

/**
 * Lesson Model
 */
class Lesson
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all lessons with module info
     */
    public function getAllLessons(): array
    {
        $query = "SELECT 
                    l.*,
                    m.title as module_title,
                    m.id as module_id,
                    q.title as quarter_title
                  FROM lessons l
                  JOIN modules m ON l.module_id = m.id
                  JOIN quarters q ON m.quarter_id = q.id
                  ORDER BY l.created_at DESC";
        
        return $this->db->query($query)->fetchAll();
    }

    /**
     * Get lesson by ID
     */
    public function getById(int $lessonId): ?array
    {
        $query = "SELECT 
                    l.*,
                    m.title as module_title,
                    q.title as quarter_title
                  FROM lessons l
                  JOIN modules m ON l.module_id = m.id
                  JOIN quarters q ON m.quarter_id = q.id
                  WHERE l.id = ?";
        
        return $this->db->query($query, [$lessonId])->fetch();
    }

    /**
     * Create lesson
     */
    public function create(array $data): ?int
    {
        $query = "INSERT INTO lessons (module_id, slug, title, summary, intro_html, examples_html, practice_html, xp_reward, is_published) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->execute($query, [
            $data['module_id'],
            $data['slug'] ?? $this->generateSlug($data['title']),
            $data['title'],
            $data['summary'] ?? '',
            $data['intro_html'] ?? '',
            $data['examples_html'] ?? '',
            $data['practice_html'] ?? '',
            $data['xp_reward'] ?? 10,
            $data['is_published'] ?? false
        ]);

        return $result ? $this->db->lastInsertId() : null;
    }

    /**
     * Update lesson
     */
    public function update(int $lessonId, array $data): bool
    {
        $updates = [];
        $params = [];

        $allowedFields = ['title', 'summary', 'intro_html', 'examples_html', 'practice_html', 'xp_reward', 'is_published'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $lessonId;
        $query = "UPDATE lessons SET " . implode(", ", $updates) . " WHERE id = ?";
        
        return $this->db->execute($query, $params);
    }

    /**
     * Delete lesson
     */
    public function delete(int $lessonId): bool
    {
        $query = "DELETE FROM lessons WHERE id = ?";
        return $this->db->execute($query, [$lessonId]);
    }

    /**
     * Get lessons by module
     */
    public function getByModule(int $moduleId): array
    {
        $query = "SELECT * FROM lessons WHERE module_id = ? ORDER BY sort_order, id";
        return $this->db->query($query, [$moduleId])->fetchAll();
    }

    /**
     * Generate slug from title
     */
    private function generateSlug(string $title): string
    {
        $text = strtolower($title);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }

    /**
     * Get lesson statistics
     */
    public function getLessonStats(int $lessonId): ?array
    {
        $query = "SELECT 
                    l.id,
                    l.title,
                    COUNT(DISTINCT slp.student_id) as students_started,
                    SUM(CASE WHEN slp.is_completed = TRUE THEN 1 ELSE 0 END) as students_completed,
                    AVG(qa.percentage) as average_quiz_score
                  FROM lessons l
                  LEFT JOIN student_lesson_progress slp ON l.id = slp.lesson_id
                  LEFT JOIN quiz_questions qq ON l.id = qq.lesson_id
                  LEFT JOIN quiz_attempts qa ON qq.lesson_id = qa.lesson_id
                  WHERE l.id = ?
                  GROUP BY l.id";
        
        return $this->db->query($query, [$lessonId])->fetch();
    }
}
