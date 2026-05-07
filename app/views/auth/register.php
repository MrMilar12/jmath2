<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        .register-header p {
            color: #888;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 12px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-select {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 12px;
            font-size: 14px;
        }
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
        .error-text {
            color: #721c24;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>jmath2</h1>
            <p>Create Your Account</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="error-message">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
        <div class="success-message">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/register">
            <div class="form-group">
                <label for="display_name" class="form-label">Full Name</label>
                <input type="text" 
                       class="form-control" 
                       id="display_name" 
                       name="display_name" 
                       placeholder="Enter your full name"
                       value="<?= htmlspecialchars($formData['display_name'] ?? '') ?>"
                       required>
                <?php if (isset($errors['display_name'])): ?>
                <div class="error-text"><?= htmlspecialchars($errors['display_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       placeholder="Enter your email"
                       value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                       required>
                <?php if (isset($errors['email'])): ?>
                <div class="error-text"><?= htmlspecialchars($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <?php if ($userType === 'student'): ?>
            <div class="form-group">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" 
                       class="form-control" 
                       id="student_id" 
                       name="student_id" 
                       placeholder="Enter your student ID"
                       value="<?= htmlspecialchars($formData['student_id'] ?? '') ?>">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Enter your password (min. 6 characters)"
                       required>
                <?php if (isset($errors['password'])): ?>
                <div class="error-text"><?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password_confirm" 
                       name="password_confirm" 
                       placeholder="Confirm your password"
                       required>
                <?php if (isset($errors['password_confirm'])): ?>
                <div class="error-text"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                <?php endif; ?>
            </div>

            <input type="hidden" name="role" value="<?= htmlspecialchars($userType) ?>">

            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="form-footer">
            <p>Already have an account? <a href="/login">Sign in here</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
