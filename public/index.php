<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/');
$path = $path === '' ? '/' : $path;

if (!$isInstalled && !str_starts_with($path, '/install')) {
    redirect_to('/install/');
}

if ($path === '/install' || $path === '/install/') {
    require dirname(__DIR__) . '/install/index.php';
    exit;
}

$segments = array_values(array_filter(explode('/', trim($path, '/'))));
$route = $segments[0] ?? 'home';

$lessons = get_lessons($pdo);

function page_header(string $title): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="/assets/styles.css"></head><body><div class="clouds"></div><div class="page-wrap">';
    echo '<div class="topbar"><div class="brand">JMath2 Bubble Quest</div><a class="btn" href="/">Home</a></div>';
}

function page_footer(): void
{
    echo '</div><div class="footer-grass"></div><script src="/assets/app.js"></script></body></html>';
}

if ($route === 'home') {
    page_header('JMath2 - Bubble Lessons');
    echo '<section class="hero"><h1>Tap a Bubble, Learn Math!</h1><p>Kid-friendly lessons, then quick quizzes for easy learning.</p></section>';
    echo '<section class="bubble-grid">';
    foreach ($lessons as $lesson) {
        $completed = !empty($_SESSION['passed'][$lesson['slug']]);
        $status = $completed ? 'Quiz Passed!' : 'Start Lesson';
        echo '<a class="bubble" href="/lesson/' . e($lesson['slug']) . '"><strong>' . e($lesson['title']) . '</strong><span>' . e($status) . '</span></a>';
    }
    echo '</section>';
    page_footer();
    exit;
}

if ($route === 'lesson') {
    $slug = sanitize_slug($segments[1] ?? '');
    $lesson = get_lesson_by_slug($pdo, $slug);

    if (!$lesson) {
        http_response_code(404);
        page_header('Lesson Not Found');
        echo '<div class="card"><p class="notice">Lesson not found.</p></div>';
        page_footer();
        exit;
    }

    page_header($lesson['title']);
    echo '<article class="card">';
    echo '<h2>' . e($lesson['title']) . '</h2>';
    echo '<p>' . e($lesson['summary']) . '</p>';
    echo '<div>' . $lesson['content_html'] . '</div>';
    echo '<p><a class="btn" href="/quiz/' . e($lesson['slug']) . '">Take Quiz</a></p>';
    echo '</article>';
    page_footer();
    exit;
}

if ($route === 'quiz') {
    $slug = sanitize_slug($segments[1] ?? '');
    $lesson = get_lesson_by_slug($pdo, $slug);

    if (!$lesson) {
        http_response_code(404);
        page_header('Quiz Not Found');
        echo '<div class="card"><p class="notice">Quiz not found.</p></div>';
        page_footer();
        exit;
    }

    $questions = get_questions_for_lesson($pdo, (int)$lesson['id']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf'] ?? null)) {
            http_response_code(400);
            page_header('Invalid Request');
            echo '<div class="card"><p class="notice">Invalid request token. Please retry.</p></div>';
            page_footer();
            exit;
        }

        $correct = 0;
        $total = count($questions);
        $review = [];

        foreach ($questions as $q) {
            $name = 'q_' . $q['id'];
            $answer = strtolower((string)($_POST[$name] ?? ''));
            $isCorrect = $answer === strtolower($q['correct_option']);
            if ($isCorrect) {
                $correct++;
            }

            $review[] = [
                'question' => $q['question'],
                'is_correct' => $isCorrect,
                'correct' => strtoupper($q['correct_option']),
                'explanation' => $q['explanation'],
            ];
        }

        $score = $total > 0 ? (int)round(($correct / $total) * 100) : 0;
        if ($score >= 70) {
            $_SESSION['passed'][$lesson['slug']] = true;
        }

        page_header($lesson['title'] . ' - Result');
        echo '<section class="card">';
        echo '<h2>Quiz Result: ' . e($lesson['title']) . '</h2>';
        echo '<p class="notice">Score: ' . $score . '% (' . $correct . '/' . $total . ')</p>';
        foreach ($review as $item) {
            echo '<div class="question">';
            echo '<strong>' . e($item['question']) . '</strong><br>';
            echo $item['is_correct']
                ? '<span style="color:#1f9d55; font-weight:700;">Correct</span>'
                : '<span style="color:#d64545; font-weight:700;">Incorrect</span> | Correct Answer: ' . e($item['correct']);
            echo '<p>' . e($item['explanation']) . '</p>';
            echo '</div>';
        }
        echo '<p><a class="btn" href="/">Back to Bubbles</a></p>';
        echo '</section>';
        page_footer();
        exit;
    }

    page_header($lesson['title'] . ' - Quiz');
    echo '<section class="card"><h2>' . e($lesson['title']) . ' Quiz</h2>';
    echo '<form method="post" action="">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';

    foreach ($questions as $index => $q) {
        echo '<div class="question">';
        echo '<p><strong>Q' . ($index + 1) . '.</strong> ' . e($q['question']) . '</p>';
        echo '<div class="options">';

        foreach (['a', 'b', 'c', 'd'] as $opt) {
            $field = 'option_' . $opt;
            $name = 'q_' . $q['id'];
            $value = $opt;
            echo '<label class="option"><input required type="radio" name="' . e($name) . '" value="' . e($value) . '">';
            echo '<span><strong>' . strtoupper($opt) . '.</strong> ' . e($q[$field]) . '</span></label>';
        }

        echo '</div></div>';
    }

    echo '<button class="btn" type="submit">Submit Quiz</button>';
    echo '</form></section>';
    page_footer();
    exit;
}

http_response_code(404);
page_header('Not Found');
echo '<div class="card"><p class="notice">Page not found.</p></div>';
page_footer();
