-- ==========================================
-- jmath2 - Web-Based Mathematics Learning Platform
-- Enhanced Database Schema
-- ==========================================

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  email VARCHAR(180) NOT NULL UNIQUE,
  student_id VARCHAR(80) NULL UNIQUE,
  username VARCHAR(80) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(140) NOT NULL,
  xp INT NOT NULL DEFAULT 0,
  level_name VARCHAR(40) NOT NULL DEFAULT 'Beginner',
  is_active BOOLEAN DEFAULT TRUE,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role),
  INDEX idx_xp (xp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- QUARTERS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS quarters (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  description TEXT,
  sort_order INT NOT NULL DEFAULT 1,
  is_active BOOLEAN DEFAULT TRUE,
  academic_year VARCHAR(9),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- MODULES TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS modules (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  quarter_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  slug VARCHAR(160) UNIQUE,
  sort_order INT NOT NULL DEFAULT 1,
  is_published BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_module_quarter FOREIGN KEY (quarter_id) REFERENCES quarters(id) ON DELETE CASCADE,
  INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- LESSONS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS lessons (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  module_id INT UNSIGNED NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  summary VARCHAR(255) NOT NULL,
  intro_html TEXT NOT NULL,
  examples_html TEXT NOT NULL,
  practice_html TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 1,
  xp_reward INT DEFAULT 10,
  duration_minutes INT,
  is_published BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lesson_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
  INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- ACTIVITIES TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS activities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  content LONGTEXT,
  activity_type ENUM('interactive', 'guided_practice', 'independent') DEFAULT 'interactive',
  sort_order INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- QUIZ QUESTIONS TABLE (Enhanced)
-- ==========================================
CREATE TABLE IF NOT EXISTS quiz_questions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  quiz_type ENUM('pre','post','practice') NOT NULL DEFAULT 'post',
  question VARCHAR(255) NOT NULL,
  question_kind ENUM('mcq','fill_blank','drag_drop','graph','true_false') NOT NULL DEFAULT 'mcq',
  option_a VARCHAR(180) NULL,
  option_b VARCHAR(180) NULL,
  option_c VARCHAR(180) NULL,
  option_d VARCHAR(180) NULL,
  correct_option CHAR(1) NULL,
  correct_text VARCHAR(180) NULL,
  explanation VARCHAR(255) NOT NULL,
  difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
  points INT DEFAULT 1,
  randomize_choices BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_question_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  INDEX idx_quiz_type (quiz_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- QUIZ ATTEMPTS TABLE (Enhanced)
-- ==========================================
CREATE TABLE IF NOT EXISTS quiz_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  quiz_type ENUM('pre','post','practice') NOT NULL DEFAULT 'post',
  score INT NOT NULL,
  total_items INT NOT NULL,
  percentage DECIMAL(5, 2),
  passed BOOLEAN,
  time_spent_seconds INT,
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  CONSTRAINT fk_attempt_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_attempt_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  INDEX idx_student_id (student_id),
  INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- QUESTION RESPONSES TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS question_responses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  quiz_attempt_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  user_response TEXT,
  is_correct BOOLEAN,
  points_earned INT DEFAULT 0,
  answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_response_attempt FOREIGN KEY (quiz_attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_response_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- LESSON PROGRESS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS student_lesson_progress (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  completed_at DATETIME NULL,
  completion_percent INT NOT NULL DEFAULT 0,
  xp_earned INT DEFAULT 0,
  is_completed BOOLEAN DEFAULT FALSE,
  started_at TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_lesson (student_id, lesson_id),
  CONSTRAINT fk_progress_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  INDEX idx_completed (is_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- MODULE PROGRESS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS module_progress (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  module_id INT UNSIGNED NOT NULL,
  completed_lessons INT DEFAULT 0,
  total_lessons INT DEFAULT 0,
  progress_percentage INT DEFAULT 0,
  is_completed BOOLEAN DEFAULT FALSE,
  started_at TIMESTAMP,
  completed_at DATETIME NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_module (student_id, module_id),
  CONSTRAINT fk_mod_progress_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mod_progress_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
  INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- BADGES TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS badges (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  title VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon_url VARCHAR(255),
  requirement_type ENUM('xp', 'score', 'completion', 'streak') DEFAULT 'xp',
  required_xp INT NOT NULL DEFAULT 0,
  requirement_value INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- STUDENT BADGES TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS student_badges (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  badge_id INT UNSIGNED NOT NULL,
  awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_badge (student_id, badge_id),
  CONSTRAINT fk_sb_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sb_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
  INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- LEADERBOARD TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS leaderboard (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  total_xp INT DEFAULT 0,
  rank INT,
  weekly_xp INT DEFAULT 0,
  weekly_rank INT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user (user_id),
  CONSTRAINT fk_leaderboard_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_rank (rank),
  INDEX idx_weekly_rank (weekly_rank)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- DAILY STREAKS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS student_daily_streaks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  current_streak INT DEFAULT 0,
  longest_streak INT DEFAULT 0,
  last_activity_date DATE,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_streak (student_id),
  CONSTRAINT fk_streak_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- NOTIFICATIONS TABLE (Enhanced)
-- ==========================================
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('incomplete_module', 'new_activity', 'quiz_reminder', 'badge_earned', 'low_performance', 'submission') DEFAULT 'new_activity',
  title VARCHAR(255),
  message VARCHAR(255) NOT NULL,
  related_id INT,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_read (is_read),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- CLASS ENROLLMENTS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS class_enrollments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED,
  class_code VARCHAR(50),
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enrollment (student_id, class_code),
  CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_enroll_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_student_id (student_id),
  INDEX idx_teacher_id (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- ACTIVITY LOGS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  action VARCHAR(100),
  resource_type VARCHAR(100),
  resource_id INT,
  details JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- PASSWORD RESETS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- SAMPLE DATA INSERTION
-- ==========================================

-- Insert Admin User
INSERT INTO users (role, email, username, password_hash, display_name)
SELECT 'admin', 'admin@jmath2.local', 'admin', '$2y$12$XCi77GLCGRbCVCCQyLeymO0VbS.uAzSOC4n7Nh5uTCKMrliKfG372', 'Main Admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role='admin' AND username='admin');

-- Insert Quarter 1
INSERT INTO quarters (title, description, sort_order, is_active, academic_year)
SELECT 'Quarter 1', 'General Mathematics - Quarter 1 Topics', 1, TRUE, '2025-2026'
WHERE NOT EXISTS (SELECT 1 FROM quarters WHERE title='Quarter 1');

-- Insert Modules
INSERT INTO modules (quarter_id, title, description, slug, sort_order, is_published)
SELECT q.id, 'Functions', 'Introduction to Functions and Function Notation', 'functions', 1, TRUE
FROM quarters q WHERE q.title='Quarter 1' 
AND NOT EXISTS (SELECT 1 FROM modules WHERE title='Functions');

INSERT INTO modules (quarter_id, title, description, slug, sort_order, is_published)
SELECT q.id, 'Rational Functions', 'Understanding Rational Functions and Graphing', 'rational-functions', 2, FALSE
FROM quarters q WHERE q.title='Quarter 1'
AND NOT EXISTS (SELECT 1 FROM modules WHERE title='Rational Functions');

INSERT INTO modules (quarter_id, title, description, slug, sort_order, is_published)
SELECT q.id, 'Inverse Functions', 'Exploring Inverse Functions and Their Properties', 'inverse-functions', 3, FALSE
FROM quarters q WHERE q.title='Quarter 1'
AND NOT EXISTS (SELECT 1 FROM modules WHERE title='Inverse Functions');

-- Insert Lessons
INSERT INTO lessons (module_id, slug, title, summary, intro_html, examples_html, practice_html, sort_order, xp_reward, is_published)
SELECT m.id, 'intro-to-functions', 'Introduction to Functions', 'Understand input-output relationships',
'<p>A function assigns exactly one output to each input.</p>',
'<p>If f(x)=x+2, then f(3)=5.</p>',
'<p>Try evaluating f(5) for f(x)=2x+1.</p>', 1, 10, TRUE
FROM modules m WHERE m.slug='functions'
AND NOT EXISTS (SELECT 1 FROM lessons WHERE slug='intro-to-functions');

-- Insert Sample Quiz Questions
INSERT INTO quiz_questions (lesson_id, quiz_type, question, question_kind, option_a, option_b, option_c, option_d, correct_option, difficulty, points, explanation)
SELECT l.id, 'post', 'If f(x)=x+2, what is f(5)?', 'mcq', '5', '6', '7', '8', 'c', 'easy', 1, '5 + 2 = 7'
FROM lessons l WHERE l.slug='intro-to-functions'
AND NOT EXISTS (SELECT 1 FROM quiz_questions WHERE lesson_id=l.id AND question='If f(x)=x+2, what is f(5)?');

-- Insert Badges
INSERT INTO badges (code, title, description, icon_url, requirement_type, required_xp)
SELECT 'problem-solver', 'Problem Solver', 'Completed 5 quizzes', '/assets/images/badges/problem-solver.png', 'completion', 100
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE code='problem-solver');

INSERT INTO badges (code, title, description, icon_url, requirement_type, required_xp)
SELECT 'math-master', 'Math Master', 'Reached 800 XP', '/assets/images/badges/math-master.png', 'xp', 800
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE code='math-master');

INSERT INTO badges (code, title, description, icon_url, requirement_type, required_xp)
SELECT 'perfect-scorer', 'Perfect Scorer', 'Got 100% on a quiz', '/assets/images/badges/perfect-scorer.png', 'score', 100
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE code='perfect-scorer');

INSERT INTO badges (code, title, description, icon_url, requirement_type, required_xp)
SELECT 'quiz-champion', 'Quiz Champion', 'Achieved a 7-day streak', '/assets/images/badges/quiz-champion.png', 'streak', 7
WHERE NOT EXISTS (SELECT 1 FROM badges WHERE code='quiz-champion');
