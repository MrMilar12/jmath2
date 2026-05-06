<?php
declare(strict_types=1);

function ensure_seed_data(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM lessons')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $lessonStmt = $pdo->prepare('INSERT INTO lessons (slug, title, summary, content_html) VALUES (?, ?, ?, ?)');
        $questionStmt = $pdo->prepare(
            'INSERT INTO quiz_questions (lesson_id, question, option_a, option_b, option_c, option_d, correct_option, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $seed = [
            [
                'slug' => 'algebra-basics',
                'title' => 'Algebra Basics',
                'summary' => 'Meet variables and simple equations.',
                'content_html' => '<p>Algebra uses letters like x to represent unknown numbers.</p><p>Example: x + 5 = 12, so x = 7.</p>',
                'questions' => [
                    ['What is x if x + 4 = 10?', '4', '6', '8', '10', 'b', 'Subtract 4 from both sides.'],
                    ['Which is a variable?', '7', 'x', '12', '3', 'b', 'Variables are symbols like x or y.'],
                    ['Solve: 2x = 14', '5', '6', '7', '8', 'c', 'Divide both sides by 2.'],
                ],
            ],
            [
                'slug' => 'functions-intro',
                'title' => 'Functions Intro',
                'summary' => 'Learn input-output relationships.',
                'content_html' => '<p>A function maps an input to exactly one output.</p><p>If f(x)=x+2, then f(3)=5.</p>',
                'questions' => [
                    ['If f(x)=x+2, what is f(5)?', '5', '6', '7', '8', 'c', '5 + 2 = 7.'],
                    ['A function gives how many outputs per input?', '0', '1', '2', 'Many', 'b', 'Exactly one output for each input.'],
                    ['If f(x)=2x, what is f(4)?', '6', '8', '10', '12', 'b', '2 times 4 is 8.'],
                ],
            ],
            [
                'slug' => 'business-math',
                'title' => 'Business Math',
                'summary' => 'Simple interest and real-life money math.',
                'content_html' => '<p>Simple Interest formula: I = P r t.</p><p>Where P is principal, r is rate, t is time.</p>',
                'questions' => [
                    ['If P=1000, r=0.1, t=1, I=?', '10', '50', '100', '1000', 'c', 'I = 1000 x 0.1 x 1 = 100.'],
                    ['In I = Prt, t means?', 'Tax', 'Time', 'Total', 'Trade', 'b', 't stands for time.'],
                    ['If r increases, interest will usually?', 'Decrease', 'Stay same', 'Increase', 'Become zero', 'c', 'Higher rate means higher interest.'],
                ],
            ],
        ];

        foreach ($seed as $lesson) {
            $lessonStmt->execute([
                $lesson['slug'],
                $lesson['title'],
                $lesson['summary'],
                $lesson['content_html'],
            ]);

            $lessonId = (int)$pdo->lastInsertId();
            foreach ($lesson['questions'] as $q) {
                $questionStmt->execute([
                    $lessonId,
                    $q[0],
                    $q[1],
                    $q[2],
                    $q[3],
                    $q[4],
                    $q[5],
                    $q[6],
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_lessons(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, slug, title, summary FROM lessons ORDER BY id ASC');
    return $stmt->fetchAll();
}

function get_lesson_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT id, slug, title, summary, content_html FROM lessons WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $lesson = $stmt->fetch();

    return $lesson ?: null;
}

function get_questions_for_lesson(PDO $pdo, int $lessonId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, question, option_a, option_b, option_c, option_d, correct_option, explanation FROM quiz_questions WHERE lesson_id = ? ORDER BY id ASC'
    );
    $stmt->execute([$lessonId]);

    return $stmt->fetchAll();
}
