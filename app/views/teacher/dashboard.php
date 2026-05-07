<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 24px;
            color: white !important;
        }

        .sidebar {
            background: white;
            padding: 2rem;
            border-right: 1px solid #eee;
            min-height: 100vh;
        }

        .sidebar a {
            display: block;
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #f0f0f0;
            color: #667eea;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #999;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .table-responsive {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        table {
            margin-bottom: 0;
        }

        table th {
            background: #f9f9f9;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }

        table tbody tr:hover {
            background: #f9f9f9;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #63428c 100%);
        }

        h1, h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/teacher/dashboard">
                <i class="fas fa-graduation-cap"></i> jmath2 Teacher
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto">
                    <a href="/logout" class="btn btn-sm btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar">
                <div class="d-flex flex-column gap-2">
                    <a href="/teacher/dashboard" class="active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="/teacher/lessons">
                        <i class="fas fa-book"></i> Manage Lessons
                    </a>
                    <a href="/teacher/students">
                        <i class="fas fa-users"></i> Students
                    </a>
                    <a href="/teacher/analytics">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                    <a href="/teacher/export">
                        <i class="fas fa-download"></i> Export Data
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9" style="padding: 2rem;">
                <h1><i class="fas fa-tachometer-alt"></i> Teacher Dashboard</h1>

                <!-- Overview Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>Total Students</h3>
                            <div class="value"><?= $stats['total_students'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>Lessons</h3>
                            <div class="value"><?= $stats['total_lessons'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>Quiz Attempts</h3>
                            <div class="value"><?= $stats['total_attempts'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3>Avg Score</h3>
                            <div class="value"><?= round($stats['average_score'] ?? 0, 1) ?>%</div>
                        </div>
                    </div>
                </div>

                <!-- Student Performance Table -->
                <h2><i class="fas fa-chart-bar"></i> Student Performance</h2>
                <div class="table-responsive mb-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>XP</th>
                                <th>Level</th>
                                <th>Attempts</th>
                                <th>Avg Score</th>
                                <th>Best Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($student['display_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                                <td><?= $student['xp'] ?? 0 ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($student['level_name']) ?>
                                    </span>
                                </td>
                                <td><?= $student['attempts'] ?? 0 ?></td>
                                <td><?= round($student['average_score'] ?? 0, 1) ?>%</td>
                                <td><?= round($student['best_score'] ?? 0, 1) ?>%</td>
                                <td>
                                    <a href="/teacher/student/<?= $student['id'] ?>" class="btn btn-sm btn-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Attempts -->
                <h2><i class="fas fa-history"></i> Recent Quiz Attempts</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Lesson</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAttempts as $attempt): ?>
                            <tr>
                                <td><?= htmlspecialchars($attempt['display_name']) ?></td>
                                <td><?= htmlspecialchars($attempt['lesson_title']) ?></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?= round($attempt['score_percentage'] ?? 0, 1) ?>%
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($attempt['completed_at'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
