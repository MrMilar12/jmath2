<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .progress-bar-custom {
            height: 6px;
            background: #eee;
            border-radius: 3px;
            margin-top: 1rem;
            overflow: hidden;
        }

        .progress-bar-custom > div {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .lessons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .lesson-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .lesson-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }

        .lesson-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .lesson-card-header h3 {
            font-size: 18px;
            margin-bottom: 0.5rem;
        }

        .lesson-card-header p {
            font-size: 12px;
            opacity: 0.9;
        }

        .lesson-card-body {
            padding: 1.5rem;
        }

        .lesson-badge {
            display: inline-block;
            background: #f0f0f0;
            color: #666;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .lesson-completed {
            background: #d4edda;
            color: #155724;
        }

        .btn-lesson {
            display: inline-block;
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: opacity 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-lesson:hover {
            opacity: 0.9;
            color: white;
            text-decoration: none;
        }

        .badges-section {
            margin: 2rem 0;
        }

        .badge-item {
            display: inline-block;
            text-align: center;
            margin: 0.5rem;
        }

        .badge-icon {
            font-size: 48px;
            margin-bottom: 0.5rem;
        }

        .badge-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
        }

        .leaderboard-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .leaderboard-row {
            display: grid;
            grid-template-columns: 50px 1fr 80px 100px;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .leaderboard-row:last-child {
            border-bottom: none;
        }

        .leaderboard-row.header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }

        .rank-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            font-weight: 700;
        }

        .notifications-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            background: #f9f9f9;
            margin-bottom: 0.5rem;
            border-radius: 4px;
        }

        .welcome-section {
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .welcome-section h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-size: 16px;
            opacity: 0.9;
        }

        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        section {
            margin: 2rem 0;
        }

        h2 {
            color: white;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .empty-message {
            text-align: center;
            padding: 2rem;
            color: #999;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .lessons-grid {
                grid-template-columns: 1fr;
            }

            .header-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .leaderboard-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar sticky-top">
        <div class="container-custom">
            <span class="navbar-brand">jmath2</span>
            <div class="ms-auto">
                <a href="/logout" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="container-custom">
            <h1>Welcome, <?= htmlspecialchars($user['display_name']) ?>!</h1>
            <p>Master General Mathematics through interactive lessons and quizzes</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-custom">
        <!-- Stats Section -->
        <div class="header-stats">
            <div class="stat-card">
                <h3><i class="fas fa-star"></i> XP Points</h3>
                <div class="value"><?= $stats['xp'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-trophy"></i> Level</h3>
                <div class="value"><?= htmlspecialchars($stats['level_name'] ?? 'Beginner') ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-book"></i> Lessons</h3>
                <div class="value"><?= $stats['lessons_completed'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-percent"></i> Average</h3>
                <div class="value"><?= round($stats['average_score'] ?? 0, 1) ?>%</div>
            </div>
        </div>

        <!-- Notifications -->
        <?php if (!empty($notifications)): ?>
        <section class="notifications-section">
            <h3><i class="fas fa-bell"></i> Notifications</h3>
            <?php foreach ($notifications as $notif): ?>
            <div class="notification-item">
                <strong><?= htmlspecialchars($notif['title'] ?? 'Notification') ?></strong>
                <p><?= htmlspecialchars($notif['message']) ?></p>
                <small><?= htmlspecialchars($notif['created_at']) ?></small>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <!-- Lessons Section -->
        <section>
            <h2><i class="fas fa-book-open"></i> Available Lessons</h2>
            <?php if (empty($lessons)): ?>
            <div class="empty-message">
                <p>No lessons available yet. Check back soon!</p>
            </div>
            <?php else: ?>
            <div class="lessons-grid">
                <?php foreach ($lessons as $lesson): ?>
                <div class="lesson-card">
                    <div class="lesson-card-header">
                        <h3><?= htmlspecialchars($lesson['title']) ?></h3>
                        <p><?= htmlspecialchars($lesson['module_title']) ?></p>
                    </div>
                    <div class="lesson-card-body">
                        <?php if ($lesson['is_completed']): ?>
                        <span class="lesson-badge lesson-completed">
                            <i class="fas fa-check-circle"></i> Completed
                        </span>
                        <?php else: ?>
                        <span class="lesson-badge">In Progress</span>
                        <?php endif; ?>
                        
                        <div class="progress-bar-custom">
                            <div style="width: <?= $lesson['progress_percentage'] ?? 0 ?>%"></div>
                        </div>
                        
                        <?php if ($lesson['xp_earned']): ?>
                        <p style="margin-top: 0.5rem; color: #667eea; font-weight: 600;">
                            <i class="fas fa-star"></i> +<?= $lesson['xp_earned'] ?> XP
                        </p>
                        <?php endif; ?>

                        <a href="/lesson/<?= htmlspecialchars($lesson['slug']) ?>" class="btn-lesson" style="margin-top: 1rem;">
                            <?= $lesson['is_completed'] ? 'Review Lesson' : 'Start Lesson' ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Badges Section -->
        <section class="badges-section">
            <h2><i class="fas fa-medal"></i> Badges Earned</h2>
            <?php if (empty($badges)): ?>
            <div class="empty-message">
                <p>Complete quizzes and lessons to earn badges!</p>
            </div>
            <?php else: ?>
            <div>
                <?php foreach ($badges as $badge): ?>
                <div class="badge-item">
                    <div class="badge-icon">⭐</div>
                    <div class="badge-name"><?= htmlspecialchars($badge['title']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Leaderboard Section -->
        <section>
            <h2><i class="fas fa-ranking-star"></i> Leaderboard</h2>
            <div class="leaderboard-table">
                <div class="leaderboard-row header">
                    <div>Rank</div>
                    <div>Name</div>
                    <div>XP</div>
                    <div>Level</div>
                </div>
                <?php if (!empty($leaderboardRank)): ?>
                <div class="leaderboard-row">
                    <div><span class="rank-badge"><?= $leaderboardRank['rank'] ?? '???' ?></span></div>
                    <div><strong>You</strong></div>
                    <div><?= $leaderboardRank['total_xp'] ?? 0 ?></div>
                    <div><?= htmlspecialchars($user['level_name']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <a href="/leaderboard" class="btn btn-primary mt-3" style="width: 100%;">View Full Leaderboard</a>
        </section>
    </div>

    <!-- Footer -->
    <footer style="background: rgba(0, 0, 0, 0.1); color: white; padding: 2rem; margin-top: 3rem; text-align: center;">
        <p>&copy; 2025 jmath2 - Interactive Mathematics Learning Platform</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    <script>
        // Simple animations on load
        gsap.from('.stat-card', {
            duration: 0.6,
            y: 20,
            opacity: 0,
            stagger: 0.1
        });

        gsap.from('.lesson-card', {
            duration: 0.6,
            y: 20,
            opacity: 0,
            stagger: 0.1,
            delay: 0.3
        });
    </script>
</body>
</html>
