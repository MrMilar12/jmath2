<?php
declare(strict_types=1);

function ensure_schema_ready(PDO $pdo): void
{
    $check = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($check && $check->fetchColumn()) {
        return;
    }

    $schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Schema file missing.');
    }
    $pdo->exec($schema);
}

function create_student(PDO $pdo, string $name, string $email, string $studentId, string $password): void
{
    $stmt = $pdo->prepare('INSERT INTO users (role, email, student_id, password_hash, display_name) VALUES (\'student\', ?, ?, ?, ?)');
    $stmt->execute([strtolower($email), $studentId, password_hash($password, PASSWORD_DEFAULT), $name]);
}

function find_user_by_login(PDO $pdo, string $login, string $role): ?array
{
    if ($role === 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE role = \'admin\' AND (username = ? OR email = ?) LIMIT 1');
        $stmt->execute([$login, strtolower($login)]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE role = \'student\' AND (email = ? OR student_id = ?) LIMIT 1');
        $stmt->execute([strtolower($login), $login]);
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_password_reset_token(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(24));
    $expires = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $token, $expires]);
    return $token;
}

function find_password_reset(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mark_reset_token_used(PDO $pdo, int $resetId): void
{
    $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?');
    $stmt->execute([$resetId]);
}

function update_user_password(PDO $pdo, int $userId, string $password): void
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
}

function get_quarters_with_modules(PDO $pdo): array
{
    $sql = 'SELECT q.id qid, q.title qtitle, m.id mid, m.title mtitle, m.description mdesc
            FROM quarters q
            LEFT JOIN modules m ON m.quarter_id = q.id
            ORDER BY q.sort_order, m.sort_order';
    $rows = $pdo->query($sql)->fetchAll();

    $out = [];
    foreach ($rows as $r) {
        $qid = (int)$r['qid'];
        if (!isset($out[$qid])) {
            $out[$qid] = [
                'id' => $qid,
                'title' => $r['qtitle'],
                'modules' => [],
            ];
        }
        if (!empty($r['mid'])) {
            $out[$qid]['modules'][] = [
                'id' => (int)$r['mid'],
                'title' => $r['mtitle'],
                'description' => $r['mdesc'],
            ];
        }
    }

    return array_values($out);
}

function get_lessons(PDO $pdo): array
{
    return $pdo->query('SELECT l.*, m.title module_title, q.title quarter_title
                        FROM lessons l
                        INNER JOIN modules m ON m.id = l.module_id
                        INNER JOIN quarters q ON q.id = m.quarter_id
                        ORDER BY q.sort_order, m.sort_order, l.sort_order')->fetchAll();
}

function get_lessons_by_module(PDO $pdo, int $moduleId): array
{
    $stmt = $pdo->prepare('SELECT * FROM lessons WHERE module_id = ? ORDER BY sort_order');
    $stmt->execute([$moduleId]);
    return $stmt->fetchAll();
}

function get_lesson_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT l.*, m.title module_title, q.title quarter_title
                           FROM lessons l
                           INNER JOIN modules m ON m.id=l.module_id
                           INNER JOIN quarters q ON q.id=m.quarter_id
                           WHERE l.slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_questions_for_lesson(PDO $pdo, int $lessonId, string $quizType = 'post'): array
{
    $stmt = $pdo->prepare('SELECT * FROM quiz_questions WHERE lesson_id = ? AND quiz_type = ? ORDER BY RAND()');
    $stmt->execute([$lessonId, $quizType]);
    return $stmt->fetchAll();
}

function save_quiz_attempt(PDO $pdo, int $studentId, int $lessonId, string $quizType, int $score, int $totalItems): void
{
    $stmt = $pdo->prepare('INSERT INTO quiz_attempts (student_id, lesson_id, quiz_type, score, total_items) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$studentId, $lessonId, $quizType, $score, $totalItems]);

    $completion = min(100, max(20, $score));
    $upsert = $pdo->prepare('INSERT INTO student_lesson_progress (student_id, lesson_id, completed_at, completion_percent)
                             VALUES (?, ?, NOW(), ?)
                             ON DUPLICATE KEY UPDATE completed_at = VALUES(completed_at), completion_percent = GREATEST(completion_percent, VALUES(completion_percent))');
    $upsert->execute([$studentId, $lessonId, $completion]);

    $earned = (int)max(20, round($score * 1.5));
    award_xp($pdo, $studentId, $earned, 'Quiz completion');
    sync_badges($pdo, $studentId);
}

function award_xp(PDO $pdo, int $studentId, int $xp, string $reason): void
{
    $stmt = $pdo->prepare('UPDATE users SET xp = xp + ? WHERE id = ? AND role = \'student\'');
    $stmt->execute([$xp, $studentId]);

    $row = get_student_row($pdo, $studentId);
    if ($row) {
        $level = get_level_from_xp((int)$row['xp']);
        $up = $pdo->prepare('UPDATE users SET level_name = ? WHERE id = ?');
        $up->execute([$level, $studentId]);
        create_notification($pdo, $studentId, '+' . $xp . ' XP earned: ' . $reason);
    }
}

function sync_badges(PDO $pdo, int $studentId): void
{
    $student = get_student_row($pdo, $studentId);
    if (!$student) {
        return;
    }

    $badges = $pdo->query('SELECT id, title, required_xp FROM badges ORDER BY required_xp')->fetchAll();
    $insert = $pdo->prepare('INSERT IGNORE INTO student_badges (student_id, badge_id) VALUES (?, ?)');
    foreach ($badges as $badge) {
        if ((int)$student['xp'] >= (int)$badge['required_xp']) {
            $insert->execute([$studentId, (int)$badge['id']]);
            create_notification($pdo, $studentId, 'Badge unlocked: ' . (string)$badge['title']);
        }
    }
}

function get_student_row(PDO $pdo, int $studentId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = \'student\' LIMIT 1');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_student_stats(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) attempts, COALESCE(AVG(score),0) avg_score, COALESCE(MAX(score),0) best_score FROM quiz_attempts WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $base = $stmt->fetch() ?: ['attempts' => 0, 'avg_score' => 0, 'best_score' => 0];

    $progress = $pdo->prepare('SELECT COALESCE(AVG(completion_percent),0) progress_percent FROM student_lesson_progress WHERE student_id = ?');
    $progress->execute([$studentId]);
    $pp = (float)$progress->fetchColumn();

    $student = get_student_row($pdo, $studentId);

    return [
        'attempts' => (int)$base['attempts'],
        'avg_score' => (float)$base['avg_score'],
        'best_score' => (float)$base['best_score'],
        'progress_percent' => $pp,
        'xp' => (int)($student['xp'] ?? 0),
        'level_name' => (string)($student['level_name'] ?? 'Beginner'),
    ];
}

function get_student_attempts(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare('SELECT qa.score, qa.quiz_type, qa.created_at, l.title lesson_title
                           FROM quiz_attempts qa
                           INNER JOIN lessons l ON l.id = qa.lesson_id
                           WHERE qa.student_id = ?
                           ORDER BY qa.created_at DESC
                           LIMIT 20');
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

function get_student_badges(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare('SELECT b.title, b.description, sb.awarded_at
                           FROM student_badges sb
                           INNER JOIN badges b ON b.id = sb.badge_id
                           WHERE sb.student_id = ?
                           ORDER BY sb.awarded_at DESC');
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

function get_leaderboard(PDO $pdo): array
{
    return $pdo->query('SELECT display_name, xp, level_name FROM users WHERE role = \'student\' ORDER BY xp DESC, display_name ASC LIMIT 10')->fetchAll();
}

function create_notification(PDO $pdo, int $userId, string $message): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
    $stmt->execute([$userId, $message]);
}

function get_user_notifications(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 12');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function mark_notification_read(PDO $pdo, int $notificationId, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $userId]);
}

function generate_incomplete_module_reminders(PDO $pdo, int $studentId): void
{
    $sql = 'SELECT l.title
            FROM lessons l
            LEFT JOIN student_lesson_progress slp ON slp.lesson_id = l.id AND slp.student_id = ?
            WHERE slp.id IS NULL OR slp.completion_percent < 70
            ORDER BY l.sort_order
            LIMIT 2';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $r) {
        create_notification($pdo, $studentId, 'Reminder: complete lesson "' . (string)$r['title'] . '".');
    }
}

function get_admin_overview(PDO $pdo): array
{
    return [
        'students' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
        'admins' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn(),
        'lessons' => (int)$pdo->query('SELECT COUNT(*) FROM lessons')->fetchColumn(),
        'attempts' => (int)$pdo->query('SELECT COUNT(*) FROM quiz_attempts')->fetchColumn(),
        'avg_score' => (float)$pdo->query('SELECT COALESCE(AVG(score),0) FROM quiz_attempts')->fetchColumn(),
    ];
}

function get_student_performance(PDO $pdo): array
{
    $sql = "SELECT u.id, u.display_name, u.email, u.student_id, u.xp, u.level_name,
                   COUNT(qa.id) attempts, COALESCE(AVG(qa.score),0) avg_score, COALESCE(MAX(qa.score),0) best_score
            FROM users u
            LEFT JOIN quiz_attempts qa ON qa.student_id = u.id
            WHERE u.role = 'student'
            GROUP BY u.id, u.display_name, u.email, u.student_id, u.xp, u.level_name
            ORDER BY avg_score DESC, attempts DESC";
    return $pdo->query($sql)->fetchAll();
}

function create_module(PDO $pdo, int $quarterId, string $title, string $description): void
{
    $stmt = $pdo->prepare('INSERT INTO modules (quarter_id, title, description, sort_order) VALUES (?, ?, ?, 999)');
    $stmt->execute([$quarterId, $title, $description]);
}

function create_lesson(PDO $pdo, int $moduleId, string $title, string $summary, string $intro, string $examples, string $practice): void
{
    $slug = make_slug($title) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $stmt = $pdo->prepare('INSERT INTO lessons (module_id, slug, title, summary, intro_html, examples_html, practice_html, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, 999)');
    $stmt->execute([$moduleId, $slug, $title, $summary, $intro, $examples, $practice]);
}

function create_question(PDO $pdo, int $lessonId, string $quizType, string $question, array $choices, string $correctOption, string $explanation): void
{
    $stmt = $pdo->prepare('INSERT INTO quiz_questions (lesson_id, quiz_type, question, question_kind, option_a, option_b, option_c, option_d, correct_option, explanation)
                           VALUES (?, ?, ?, \'mcq\', ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$lessonId, $quizType, $question, $choices['a'], $choices['b'], $choices['c'], $choices['d'], strtolower($correctOption), $explanation]);
}
