<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($uri) ? trim($uri) : '/';
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

if ($pdo instanceof PDO) {
    $lessons = get_lessons($pdo);
} else {
    $lessons = [];
}

function page_header(string $title): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="/assets/styles.css"></head><body><div class="bg-layer"></div><main class="app-shell">';

    $nav = '';
    if (is_student_logged_in()) {
        $nav .= '<a class="btn tiny" href="/">Dashboard</a><a class="btn tiny alt" href="/logout">Logout</a>';
    } else {
        $nav .= '<a class="btn tiny" href="/login">Student Login</a><a class="btn tiny alt" href="/register">Register</a>';
    }
    if (is_admin_logged_in()) {
        $nav .= '<a class="btn tiny" href="/admin">Admin</a><a class="btn tiny danger" href="/admin/logout">Admin Logout</a>';
    } else {
        $nav .= '<a class="btn tiny alt" href="/admin/login">Admin Login</a>';
    }

    echo '<header class="topbar"><div class="brand">JMath2 Quest</div><nav class="nav">' . $nav . '</nav></header>';
}

function page_footer(): void
{
    echo '</main><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script src="/assets/app.js"></script></body></html>';
}

if ($route === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf'] ?? null)) {
            $_SESSION['flash_err'] = 'Invalid form token.';
            redirect_to('/register');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $studentId = trim((string)($_POST['student_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $studentId === '' || strlen($password) < 8) {
            $_SESSION['flash_err'] = 'Complete all fields. Password must be at least 8 characters.';
            redirect_to('/register');
        }

        try {
            create_student($pdo, $name, $email, $studentId, $password);
            $_SESSION['flash_ok'] = 'Registration complete. Please log in.';
            redirect_to('/login');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = 'Email or Student ID already used.';
            redirect_to('/register');
        }
    }

    page_header('Student Register');
    $err = (string)($_SESSION['flash_err'] ?? '');
    unset($_SESSION['flash_err']);
    echo '<section class="card"><h2>Create Student Account</h2>';
    if ($err !== '') echo '<p class="notice err">' . e($err) . '</p>';
    echo '<form method="post" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Full Name<input class="input" required name="name"></label>';
    echo '<label>Email<input class="input" required type="email" name="email"></label>';
    echo '<label>Student ID<input class="input" required name="student_id"></label>';
    echo '<label>Password<input class="input" required type="password" name="password"></label>';
    echo '<button class="btn" type="submit">Register</button>';
    echo '</form></section>';
    page_footer();
    exit;
}

if ($route === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf'] ?? null)) {
            $_SESSION['flash_err'] = 'Invalid form token.';
            redirect_to('/login');
        }

        $login = trim((string)($_POST['login'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $student = find_user_by_login($pdo, $login, 'student');

        if ($student && password_verify($password, (string)$student['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['student'] = [
                'id' => (int)$student['id'],
                'name' => (string)$student['display_name'],
                'email' => (string)$student['email'],
            ];
            generate_incomplete_module_reminders($pdo, (int)$student['id']);
            redirect_to('/');
        }

        $_SESSION['flash_err'] = 'Invalid credentials.';
        redirect_to('/login');
    }

    page_header('Student Login');
    $ok = (string)($_SESSION['flash_ok'] ?? '');
    $err = (string)($_SESSION['flash_err'] ?? '');
    unset($_SESSION['flash_ok'], $_SESSION['flash_err']);
    echo '<section class="card"><h2>Student Login</h2>';
    if ($ok !== '') echo '<p class="notice ok">' . e($ok) . '</p>';
    if ($err !== '') echo '<p class="notice err">' . e($err) . '</p>';
    echo '<form method="post" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Email or Student ID<input class="input" required name="login"></label>';
    echo '<label>Password<input class="input" required type="password" name="password"></label>';
    echo '<button class="btn" type="submit">Login</button>';
    echo '<a href="/forgot-password">Forgot password?</a>';
    echo '</form></section>';
    page_footer();
    exit;
}

if ($route === 'forgot-password') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf'] ?? null)) {
            $_SESSION['flash_err'] = 'Invalid form token.';
            redirect_to('/forgot-password');
        }
        $login = trim((string)($_POST['login'] ?? ''));
        $student = find_user_by_login($pdo, $login, 'student');
        if ($student) {
            $token = create_password_reset_token($pdo, (int)$student['id']);
            $_SESSION['reset_link'] = '/reset-password?token=' . $token;
        }
        $_SESSION['flash_ok'] = 'If account exists, reset link was generated.';
        redirect_to('/forgot-password');
    }

    page_header('Forgot Password');
    $ok = (string)($_SESSION['flash_ok'] ?? '');
    $err = (string)($_SESSION['flash_err'] ?? '');
    $link = (string)($_SESSION['reset_link'] ?? '');
    unset($_SESSION['flash_ok'], $_SESSION['flash_err']);
    echo '<section class="card"><h2>Forgot Password</h2>';
    if ($ok !== '') echo '<p class="notice ok">' . e($ok) . '</p>';
    if ($err !== '') echo '<p class="notice err">' . e($err) . '</p>';
    if ($link !== '') echo '<p class="notice">Demo reset link: <a href="' . e($link) . '">' . e($link) . '</a></p>';
    echo '<form method="post" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Email or Student ID<input class="input" required name="login"></label>';
    echo '<button class="btn" type="submit">Generate Reset Link</button>';
    echo '</form></section>';
    page_footer();
    exit;
}

if ($route === 'reset-password') {
    $token = trim((string)($_GET['token'] ?? ''));
    $reset = $token !== '' ? find_password_reset($pdo, $token) : null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf'] ?? null)) {
            $_SESSION['flash_err'] = 'Invalid form token.';
            redirect_to('/reset-password?token=' . urlencode($token));
        }

        $password = (string)($_POST['password'] ?? '');
        if (!$reset || $reset['used_at'] !== null || strtotime((string)$reset['expires_at']) < time()) {
            $_SESSION['flash_err'] = 'Reset token invalid or expired.';
            redirect_to('/login');
        }
        if (strlen($password) < 8) {
            $_SESSION['flash_err'] = 'Password must be at least 8 characters.';
            redirect_to('/reset-password?token=' . urlencode($token));
        }

        update_user_password($pdo, (int)$reset['user_id'], $password);
        mark_reset_token_used($pdo, (int)$reset['id']);
        $_SESSION['flash_ok'] = 'Password reset successful. Please log in.';
        redirect_to('/login');
    }

    page_header('Reset Password');
    $err = (string)($_SESSION['flash_err'] ?? '');
    unset($_SESSION['flash_err']);
    echo '<section class="card"><h2>Reset Password</h2>';
    if ($err !== '') echo '<p class="notice err">' . e($err) . '</p>';
    if (!$reset || $reset['used_at'] !== null || strtotime((string)$reset['expires_at']) < time()) {
        echo '<p class="notice err">Token invalid or expired.</p>';
    } else {
        echo '<form method="post" class="form-grid">';
        echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
        echo '<label>New Password<input class="input" type="password" name="password" required></label>';
        echo '<button class="btn" type="submit">Save Password</button>';
        echo '</form>';
    }
    echo '</section>';
    page_footer();
    exit;
}

if ($route === 'logout') {
    unset($_SESSION['student']);
    session_regenerate_id(true);
    redirect_to('/login');
}

if ($route === 'admin' && (($segments[1] ?? '') === 'login')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf'] ?? null)) {
            $_SESSION['admin_err'] = 'Invalid token.';
            redirect_to('/admin/login');
        }

        $login = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $admin = find_user_by_login($pdo, $login, 'admin');
        if ($admin && password_verify($password, (string)$admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = ['id' => (int)$admin['id'], 'name' => (string)$admin['display_name']];
            redirect_to('/admin');
        }
        $_SESSION['admin_err'] = 'Invalid admin credentials.';
        redirect_to('/admin/login');
    }

    page_header('Admin Login');
    $err = (string)($_SESSION['admin_err'] ?? '');
    unset($_SESSION['admin_err']);
    echo '<section class="card"><h2>Admin Login</h2><p>Default: admin / Admin12345!</p>';
    if ($err !== '') echo '<p class="notice err">' . e($err) . '</p>';
    echo '<form method="post" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Username or Email<input class="input" name="username" required></label>';
    echo '<label>Password<input class="input" type="password" name="password" required></label>';
    echo '<button class="btn" type="submit">Login</button></form></section>';
    page_footer();
    exit;
}

if ($route === 'admin' && (($segments[1] ?? '') === 'logout')) {
    unset($_SESSION['admin']);
    session_regenerate_id(true);
    redirect_to('/admin/login');
}

if ($route === 'admin' && !is_admin_logged_in() && (($segments[1] ?? '') !== 'login')) {
    redirect_to('/admin/login');
}

if ($route === 'admin' && (($segments[1] ?? '') === 'module-create') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) redirect_to('/admin');
    create_module($pdo, (int)($_POST['quarter_id'] ?? 1), trim((string)$_POST['title']), trim((string)$_POST['description']));
    redirect_to('/admin');
}

if ($route === 'admin' && (($segments[1] ?? '') === 'lesson-create') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) redirect_to('/admin');
    create_lesson(
        $pdo,
        (int)($_POST['module_id'] ?? 0),
        trim((string)$_POST['title']),
        trim((string)$_POST['summary']),
        trim((string)$_POST['intro_html']),
        trim((string)$_POST['examples_html']),
        trim((string)$_POST['practice_html'])
    );
    redirect_to('/admin');
}

if ($route === 'admin' && (($segments[1] ?? '') === 'question-create') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) redirect_to('/admin');
    create_question(
        $pdo,
        (int)($_POST['lesson_id'] ?? 0),
        trim((string)($_POST['quiz_type'] ?? 'post')),
        trim((string)$_POST['question']),
        [
            'a' => trim((string)$_POST['option_a']),
            'b' => trim((string)$_POST['option_b']),
            'c' => trim((string)$_POST['option_c']),
            'd' => trim((string)$_POST['option_d']),
        ],
        trim((string)$_POST['correct_option']),
        trim((string)$_POST['explanation'])
    );
    redirect_to('/admin');
}

if ($route === 'admin' && (($segments[1] ?? '') === 'notify') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) redirect_to('/admin');
    create_notification($pdo, (int)$_POST['student_id'], trim((string)$_POST['message']));
    redirect_to('/admin');
}

if ($route === 'admin') {
    $overview = get_admin_overview($pdo);
    $rows = get_student_performance($pdo);
    $quarters = get_quarters_with_modules($pdo);

    page_header('Admin Dashboard');
    echo '<section class="card"><h2>Teacher/Admin Dashboard</h2><div class="stats">';
    echo '<div class="stat">Students<br><strong>' . $overview['students'] . '</strong></div>';
    echo '<div class="stat">Lessons<br><strong>' . $overview['lessons'] . '</strong></div>';
    echo '<div class="stat">Attempts<br><strong>' . $overview['attempts'] . '</strong></div>';
    echo '<div class="stat">Average<br><strong>' . round((float)$overview['avg_score'], 1) . '%</strong></div>';
    echo '</div></section>';

    echo '<section class="card"><h3>Add Module</h3><form method="post" action="/admin/module-create" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Quarter<select class="input" name="quarter_id">';
    foreach ($quarters as $q) echo '<option value="' . (int)$q['id'] . '">' . e((string)$q['title']) . '</option>';
    echo '</select></label><label>Title<input class="input" name="title" required></label><label>Description<textarea class="input" name="description" rows="2" required></textarea></label><button class="btn" type="submit">Save Module</button></form></section>';

    echo '<section class="card"><h3>Add Lesson</h3><form method="post" action="/admin/lesson-create" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Module<select class="input" name="module_id">';
    foreach ($quarters as $q) {
        foreach ($q['modules'] as $m) {
            echo '<option value="' . (int)$m['id'] . '">' . e((string)$q['title']) . ' - ' . e((string)$m['title']) . '</option>';
        }
    }
    echo '</select></label>';
    echo '<label>Title<input class="input" name="title" required></label>';
    echo '<label>Summary<input class="input" name="summary" required></label>';
    echo '<label>Introduction<textarea class="input" rows="3" name="intro_html" required></textarea></label>';
    echo '<label>Interactive Examples<textarea class="input" rows="3" name="examples_html" required></textarea></label>';
    echo '<label>Practice Exercises<textarea class="input" rows="3" name="practice_html" required></textarea></label>';
    echo '<button class="btn" type="submit">Save Lesson</button></form></section>';

    echo '<section class="card"><h3>Add Quiz Question</h3><form method="post" action="/admin/question-create" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Lesson<select class="input" name="lesson_id">';
    foreach ($lessons as $l) echo '<option value="' . (int)$l['id'] . '">' . e((string)$l['title']) . '</option>';
    echo '</select></label>';
    echo '<label>Type<select class="input" name="quiz_type"><option value="pre">Pre-test</option><option value="post">Post-test</option><option value="practice">Practice</option></select></label>';
    echo '<label>Question<input class="input" name="question" required></label>';
    echo '<label>Option A<input class="input" name="option_a" required></label>';
    echo '<label>Option B<input class="input" name="option_b" required></label>';
    echo '<label>Option C<input class="input" name="option_c" required></label>';
    echo '<label>Option D<input class="input" name="option_d" required></label>';
    echo '<label>Correct Option<select class="input" name="correct_option"><option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option></select></label>';
    echo '<label>Explanation<input class="input" name="explanation" required></label>';
    echo '<button class="btn" type="submit">Add Question</button></form></section>';

    echo '<section class="card"><h3>Send Reminder</h3><form method="post" action="/admin/notify" class="form-grid">';
    echo '<input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';
    echo '<label>Student<select class="input" name="student_id">';
    foreach ($rows as $r) echo '<option value="' . (int)$r['id'] . '">' . e((string)$r['display_name']) . '</option>';
    echo '</select></label><label>Message<input class="input" name="message" required value="Please continue your incomplete modules."></label><button class="btn" type="submit">Send</button></form></section>';

    echo '<section class="card"><h3>Student Analytics</h3><div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>SID</th><th>Attempts</th><th>Avg</th><th>Best</th><th>XP</th><th>Level</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr><td>' . e((string)$r['display_name']) . '</td><td>' . e((string)$r['email']) . '</td><td>' . e((string)$r['student_id']) . '</td><td>' . (int)$r['attempts'] . '</td><td>' . round((float)$r['avg_score'], 1) . '%</td><td>' . (int)$r['best_score'] . '%</td><td>' . (int)$r['xp'] . '</td><td>' . e((string)$r['level_name']) . '</td></tr>';
    }
    echo '</tbody></table></div></section>';

    page_footer();
    exit;
}

if (!is_student_logged_in()) {
    redirect_to('/login');
}

if ($route === 'notification' && (($segments[1] ?? '') === 'read')) {
    $id = (int)($segments[2] ?? 0);
    if ($id > 0) {
        mark_notification_read($pdo, $id, (int)$_SESSION['student']['id']);
    }
    redirect_to('/');
}

if ($path === '/' || $route === 'home') {
    $studentId = (int)$_SESSION['student']['id'];
    $stats = get_student_stats($pdo, $studentId);
    $attempts = get_student_attempts($pdo, $studentId);
    $badges = get_student_badges($pdo, $studentId);
    $leaders = get_leaderboard($pdo);
    $notifications = get_user_notifications($pdo, $studentId);

    page_header('Student Dashboard');
    echo '<section class="hero"><h1>Welcome, ' . e((string)$_SESSION['student']['name']) . '!</h1><p>Quarter 1 General Mathematics, with quiz missions and rewards.</p></section>';

    echo '<section class="card"><div class="stats">';
    echo '<div class="stat">Progress<br><strong>' . round((float)$stats['progress_percent']) . '%</strong><div class="progress"><span style="width:' . round((float)$stats['progress_percent']) . '%"></span></div></div>';
    echo '<div class="stat">XP<br><strong>' . (int)$stats['xp'] . '</strong></div>';
    echo '<div class="stat">Level<br><strong>' . e((string)$stats['level_name']) . '</strong></div>';
    echo '<div class="stat">Average<br><strong>' . round((float)$stats['avg_score'], 1) . '%</strong></div>';
    echo '</div></section>';

    echo '<section class="card"><h3>Reminders</h3>';
    if (!$notifications) {
        echo '<p class="muted">No notifications yet.</p>';
    } else {
        echo '<ul class="list">';
        foreach ($notifications as $n) {
            $readTag = (int)$n['is_read'] === 1 ? 'read' : 'new';
            echo '<li><span class="pill ' . $readTag . '">' . strtoupper($readTag) . '</span> ' . e((string)$n['message']) . ' <a href="/notification/read/' . (int)$n['id'] . '">mark read</a></li>';
        }
        echo '</ul>';
    }
    echo '</section>';

    echo '<section class="bubble-grid">';
    foreach ($lessons as $lesson) {
        echo '<a class="bubble" href="/lesson/' . e((string)$lesson['slug']) . '"><strong>' . e((string)$lesson['title']) . '</strong><span>' . e((string)$lesson['module_title']) . '</span></a>';
    }
    echo '</section>';

    echo '<section class="card"><h3>Badges</h3>';
    if (!$badges) {
        echo '<p class="muted">No badges yet. Complete quizzes to unlock!</p>';
    } else {
        echo '<div class="badge-grid">';
        foreach ($badges as $b) {
            echo '<div class="badge"><strong>' . e((string)$b['title']) . '</strong><span>' . e((string)$b['description']) . '</span></div>';
        }
        echo '</div>';
    }
    echo '</section>';

    echo '<section class="card"><h3>Leaderboard</h3><div class="table-wrap"><table><thead><tr><th>#</th><th>Name</th><th>XP</th><th>Level</th></tr></thead><tbody>';
    $rank = 1;
    foreach ($leaders as $l) {
        echo '<tr><td>' . $rank++ . '</td><td>' . e((string)$l['display_name']) . '</td><td>' . (int)$l['xp'] . '</td><td>' . e((string)$l['level_name']) . '</td></tr>';
    }
    echo '</tbody></table></div></section>';

    echo '<section class="card"><h3>Recent Results</h3><div class="table-wrap"><table><thead><tr><th>Lesson</th><th>Type</th><th>Score</th><th>Date</th></tr></thead><tbody>';
    foreach ($attempts as $a) {
        echo '<tr><td>' . e((string)$a['lesson_title']) . '</td><td>' . e((string)$a['quiz_type']) . '</td><td>' . (int)$a['score'] . '%</td><td>' . e((string)$a['created_at']) . '</td></tr>';
    }
    echo '</tbody></table></div></section>';

    page_footer();
    exit;
}

if ($route === 'lesson') {
    $slug = sanitize_slug($segments[1] ?? '');
    $lesson = get_lesson_by_slug($pdo, $slug);
    if (!$lesson) {
        http_response_code(404);
        page_header('Not Found');
        echo '<section class="card"><p class="notice err">Lesson not found.</p></section>';
        page_footer();
        exit;
    }

    page_header((string)$lesson['title']);
    echo '<section class="card"><h2>' . e((string)$lesson['title']) . '</h2><p class="muted">' . e((string)$lesson['quarter_title']) . ' - ' . e((string)$lesson['module_title']) . '</p><p>' . e((string)$lesson['summary']) . '</p></section>';
    echo '<section class="card"><h3>Introduction</h3><div>' . (string)$lesson['intro_html'] . '</div></section>';
    echo '<section class="card"><h3>Interactive Example</h3><div>' . (string)$lesson['examples_html'] . '</div>';
    echo '<div class="drag-zone" data-drag-game="1"><p>Drag the correct output to the box for f(2)=?</p><div class="drag-items"><button draggable="true" class="drag-item" data-value="4">4</button><button draggable="true" class="drag-item" data-value="5">5</button><button draggable="true" class="drag-item" data-value="7">7</button></div><div class="drop-target" data-correct="4">Drop Here</div></div>';
    echo '</section>';
    echo '<section class="card"><h3>Practice</h3><div>' . (string)$lesson['practice_html'] . '</div>';
    echo '<div class="fill-check" data-fill-check="1"><label>For f(x)=2x+1, f(3)=<input class="input inline" data-correct="7"/></label><button class="btn tiny js-check-fill" type="button">Check</button><p class="fill-msg muted"></p></div>';
    echo '</section>';
    echo '<section class="card"><h3>Assessments</h3><div class="row"><a class="btn" href="/quiz/' . e((string)$lesson['slug']) . '?type=pre">Take Pre-test</a><a class="btn alt" href="/quiz/' . e((string)$lesson['slug']) . '?type=post">Take Post-test</a></div></section>';
    page_footer();
    exit;
}

if ($route === 'quiz') {
    $slug = sanitize_slug($segments[1] ?? '');
    $lesson = get_lesson_by_slug($pdo, $slug);
    if (!$lesson) {
        http_response_code(404);
        page_header('Not Found');
        echo '<section class="card"><p class="notice err">Quiz not found.</p></section>';
        page_footer();
        exit;
    }

    $quizType = trim((string)($_GET['type'] ?? 'post'));
    if (!in_array($quizType, ['pre', 'post', 'practice'], true)) {
        $quizType = 'post';
    }

    $questions = get_questions_for_lesson($pdo, (int)$lesson['id'], $quizType);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf'] ?? null)) {
            http_response_code(400);
            page_header('Invalid');
            echo '<section class="card"><p class="notice err">Invalid token.</p></section>';
            page_footer();
            exit;
        }

        $correct = 0;
        $total = count($questions);
        $review = [];
        foreach ($questions as $q) {
            $answer = strtolower((string)($_POST['q_' . $q['id']] ?? ''));
            $ok = $answer === strtolower((string)$q['correct_option']);
            if ($ok) $correct++;
            $review[] = [
                'question' => (string)$q['question'],
                'ok' => $ok,
                'correct' => strtoupper((string)$q['correct_option']),
                'explanation' => (string)$q['explanation'],
            ];
        }

        $score = $total > 0 ? (int)round(($correct / $total) * 100) : 0;
        save_quiz_attempt($pdo, (int)$_SESSION['student']['id'], (int)$lesson['id'], $quizType, $score, $total);

        page_header('Quiz Result');
        echo '<section class="card" data-quiz-result="1" data-score="' . $score . '"><h2>' . ucfirst($quizType) . ' Result: ' . e((string)$lesson['title']) . '</h2>';
        echo '<p class="notice">Score: ' . $score . '% (' . $correct . '/' . $total . ')</p>';
        foreach ($review as $item) {
            echo '<article class="review"><strong>' . e($item['question']) . '</strong><br>';
            echo $item['ok'] ? '<span class="ok-text">Correct</span>' : '<span class="err-text">Incorrect</span> | Correct: ' . e($item['correct']);
            echo '<p>' . e($item['explanation']) . '</p></article>';
        }
        echo '<p><a class="btn" href="/">Back to Dashboard</a></p></section>';
        page_footer();
        exit;
    }

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Quiz - ' . e((string)$lesson['title']) . '</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="/assets/styles.css"></head><body><main class="quiz-shell"><section class="quiz-panel">';
    echo '<h2>' . e((string)$lesson['title']) . ' (' . e(strtoupper($quizType)) . ')</h2><p class="notice">One question at a time, fullscreen style.</p>';
    echo '<form method="post" data-quiz-wizard="1"><input type="hidden" name="csrf" value="' . e(generate_csrf_token()) . '">';

    $total = count($questions);
    foreach ($questions as $i => $q) {
        echo '<div class="quiz-step" data-step="' . $i . '"><div class="question-large">';
        echo '<p><strong>Question ' . ($i + 1) . ' of ' . $total . '</strong></p>';
        echo '<p class="main-q">' . e((string)$q['question']) . '</p><div class="options-large">';
        foreach (['a', 'b', 'c', 'd'] as $opt) {
            $field = 'option_' . $opt;
            echo '<label class="option-large"><input required type="radio" name="q_' . (int)$q['id'] . '" value="' . $opt . '"><span><strong>' . strtoupper($opt) . '.</strong> ' . e((string)$q[$field]) . '</span></label>';
        }
        echo '</div></div><div class="quiz-nav">';
        if ($i > 0) echo '<button class="btn alt js-prev" type="button">Previous</button>';
        if ($i < $total - 1) echo '<button class="btn js-next" type="button">Next</button>';
        else echo '<button class="btn" type="submit">Submit Quiz</button>';
        echo '</div></div>';
    }

    echo '</form><p><a class="btn alt" href="/lesson/' . e((string)$lesson['slug']) . '">Back to Lesson</a></p>';
    echo '</section></main><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script src="/assets/app.js"></script></body></html>';
    exit;
}

http_response_code(404);
page_header('Not Found');
echo '<section class="card"><p class="notice err">Page not found.</p></section>';
page_footer();
