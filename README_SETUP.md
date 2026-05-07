# jmath2 - Web-Based Interactive Mathematics Learning Platform

A modern, engaging web-based learning platform for Senior High School General Mathematics students. Designed with gamification, interactive lessons, and real-time feedback inspired by TikTok-style learning.

## 🎯 Features

### For Students
- 📚 **Interactive Lessons** - Animated, engaging lesson content
- 📝 **Multiple Quiz Types** - Multiple choice, fill-in-the-blank, drag-and-drop, graphs
- ⭐ **Gamification System** - XP points, levels, badges, and leaderboards
- 📊 **Progress Tracking** - Visual progress bars and completion tracking
- 🏆 **Achievement System** - Earn badges for milestones and achievements
- 🔥 **Daily Streaks** - Maintain learning streaks for bonus rewards
- 📱 **Mobile Friendly** - Fully responsive design

### For Teachers/Admins
- 📋 **Lesson Management** - Create, edit, and manage lessons
- ❓ **Quiz Creation** - Build comprehensive quizzes with various question types
- 📈 **Analytics Dashboard** - Track student progress and performance
- 👥 **Student Management** - Manage student accounts and enrollments
- 📊 **Performance Reports** - Generate detailed analytics reports
- 📤 **Data Export** - Export student data for analysis

## 🏗️ System Architecture

### Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, GSAP
- **Backend**: PHP 8.0+
- **Database**: MySQL 5.7+
- **Server**: Apache/Nginx

### Project Structure
```
jmath2/
├── app/
│   ├── auth/               # Authentication classes
│   ├── controllers/        # Application controllers
│   ├── models/            # Data models
│   ├── services/          # Business logic services
│   ├── middleware/        # Middleware classes
│   ├── views/             # View templates
│   ├── core/              # Core framework files
│   ├── database/          # Database classes
│   ├── bootstrap.php      # Application bootstrap
│   └── helpers.php        # Helper functions
├── config/                # Configuration files
├── database/              # Database migrations and seeds
├── public/                # Public web root
│   ├── index.php         # Main entry point
│   ├── assets/           # CSS, JS, images
│   └── pages/            # Static pages
├── routes/               # Route definitions
├── storage/              # Uploads and logs
├── sql/                  # Database schema
└── tests/                # Test files
```

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer (optional, for package management)
- Web server (Apache/Nginx)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd jmath2
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Setup database**
   ```bash
   # Create MySQL database
   mysql -u root -p < sql/schema.sql
   ```

4. **Configure web server**
   - Set document root to `public/` directory
   - Enable URL rewriting (if using Apache)

5. **Set permissions**
   ```bash
   chmod 755 storage/
   chmod 755 storage/uploads/
   chmod 755 storage/logs/
   ```

6. **Access the application**
   - Visit: `http://localhost/` (or your configured domain)
   - Student login: `/login`
   - Admin login: `/admin/login`

## 📚 Database Schema

### Core Tables
- `users` - User accounts (students, teachers, admins)
- `quarters` - Academic quarters
- `modules` - Learning modules
- `lessons` - Individual lessons
- `quizzes` - Quiz questions and configurations
- `quiz_attempts` - Student quiz attempts
- `student_lesson_progress` - Lesson completion tracking
- `badges` - Badge definitions
- `student_badges` - Earned badges
- `leaderboard` - Student rankings

## 👨‍💻 User Roles

### Student
- View lessons and complete activities
- Take quizzes and get graded
- Earn XP and badges
- View personal progress and leaderboard ranking

### Teacher
- Create and manage lessons
- Create and manage quizzes
- View student analytics
- Export student data

### Admin
- All teacher permissions
- User management
- System configuration
- Generate reports

## 🔌 API Endpoints

### Authentication
- `POST /auth/register` - Student registration
- `POST /auth/login` - User login
- `GET /auth/logout` - User logout

### Student
- `GET /student/dashboard` - Student dashboard
- `GET /lesson/{slug}` - View lesson
- `POST /quiz/{slug}` - Submit quiz
- `GET /leaderboard` - View leaderboard
- `GET /progress` - View progress

### Teacher
- `GET /teacher/dashboard` - Teacher dashboard
- `GET /teacher/lessons` - Manage lessons
- `POST /teacher/lesson/create` - Create lesson
- `GET /teacher/analytics` - Analytics dashboard
- `GET /teacher/student/{id}` - Student analytics

## 🎮 Gamification System

### XP (Experience Points)
- 10 XP for completing a lesson
- 5 bonus XP for perfect quiz score

### Levels
- **Beginner**: 0-100 XP
- **Novice**: 100-300 XP
- **Intermediate**: 300-600 XP
- **Advanced**: 600-1000 XP
- **Expert**: 1000+ XP

### Badges
- Problem Solver (5 completed quizzes)
- Fast Thinker (<5 min quiz time)
- Algebra Master (200 XP)
- Perfect Scorer (100% on quiz)
- Quiz Champion (7-day streak)

## 🔐 Security Features

- Password hashing with bcrypt
- SQL injection prevention (prepared statements)
- CSRF protection
- Session management
- Role-based access control
- Input validation and sanitization
- HTTPS support (configurable)

## 📊 Analytics Available

- Student performance tracking
- Quiz score analysis
- Lesson completion rates
- Time spent tracking
- Progress visualization
- Class-wide statistics
- Individual achievement tracking

## 🛠️ Development

### Creating a New Controller
```php
namespace App\Controllers;

use App\Core\Controller;

class MyController extends Controller {
    public function myAction() {
        return $this->render('my-view', ['data' => $data]);
    }
}
```

### Creating a New Model
```php
namespace App\Models;

use App\Database\Database;

class MyModel {
    private Database $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
}
```

### Redirect Examples
```php
redirect('/dashboard');  // Simple redirect
return $this->error(404, 'Not found');  // Error page
return $this->json(['success' => true]);  // JSON response
```

## 📝 Helper Functions

- `redirect($url)` - Redirect to URL
- `asset($path)` - Get asset URL
- `escape($text)` - Escape HTML
- `hasRole($role)` - Check user role
- `getCurrentUserId()` - Get current user ID
- `flashMessage($key, $msg)` - Set flash message
- `slug($text)` - Generate URL slug

## 🐛 Troubleshooting

### Database Connection Error
- Check database credentials in `.env`
- Ensure MySQL is running
- Verify database exists

### Permission Errors
- Check file permissions in `storage/` directory
- Ensure web server user can write to storage

### Login Issues
- Clear browser cookies
- Check user exists in database
- Verify password hash

## 📞 Support & Contributing

For issues, suggestions, or contributions:
1. Create an issue in the repository
2. Follow the coding standards
3. Submit a pull request with detailed description

## 📄 License

This project is licensed under the MIT License - see LICENSE file for details.

## 🙏 Acknowledgments

- Bootstrap 5 for responsive design
- GSAP for smooth animations
- DepEd MELCs for curriculum alignment
- Inspired by modern ed-tech platforms

## 🔄 Update & Maintenance

### Regular Updates
- Keep PHP and dependencies updated
- Backup database regularly
- Monitor error logs in `storage/logs/`
- Update security patches

### Performance Tips
- Enable database query caching
- Optimize images
- Minify CSS/JS in production
- Use CDN for static assets

---

**Made with ❤️ for education**
