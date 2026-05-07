# JMath2 Full System Plan (Quarter 1 Scope)

## 1. Product Vision
Build a web-based interactive Senior High School General Mathematics learning module aligned to DepEd MELCs (Quarter 1), with secure student/admin roles, playful gamified UX, and low-bandwidth-friendly architecture.

## 2. Core Stack
- Backend: Pure PHP (route-based, no heavy framework)
- Frontend: HTML + CSS + JavaScript
- Database: MySQL (pure SQL schema)
- Security: session auth, CSRF, secure headers, password hashing

## 3. UI Direction (Inspired by Shared Short-Form UI)
- Bright playful interface with large touch-friendly elements
- Card-and-bubble visual language for lesson navigation
- Fullscreen single-question quiz flow (swipe-like pacing)
- Minimal text density, visual hierarchy, and immediate feedback modals

## 4. User Roles and Access
### Student
- Register via email + student ID
- Login/logout + forgot/reset password
- Access Quarter 1 learning modules, lessons, activities, quizzes
- View progress, XP, level, badges, history, reminders

### Teacher/Admin
- Secure admin login
- Create modules, lessons, and quiz questions
- Send reminders to students
- View analytics: attempts, score trends, completion, XP/level leaderboard

## 5. Learning Structure
- Quarter -> Module -> Lesson -> Activity
- Quarter 1 only for this project scope
- Every lesson includes:
  - Introduction
  - Interactive examples
  - Practice
  - Immediate feedback

## 6. Interactivity Features
- Multiple choice quizzes with instant review explanations
- Fill-in-the-blank check widgets
- Drag-and-drop mini activity
- Step-by-step prompt hints in practice widgets

## 7. Assessment System
- Pre-test and Post-test support per lesson
- Randomized question order (SQL RAND)
- Auto-check scoring
- Result page includes score + correct/incorrect + explanation

## 8. Gamification
- XP awarded per completed quiz
- Levels:
  - Beginner
  - Intermediate
  - Advanced
- Badge unlocks based on XP milestones
- Leaderboard based on XP ranking
- Reward behavior: progression and badge unlock notifications

## 9. Progress Monitoring
### Student Side
- Progress percent bar
- Completed lesson tracking
- Attempt history
- Notification list (in-app)

### Teacher Side
- Student analytics table
- Attempts, average score, best score
- XP and level visibility

## 10. Notifications and Reminders
- In-app notification engine
- Auto-generated incomplete lesson reminders on student login
- Manual reminder broadcast per student by teacher/admin
- Optional future email integration point

## 11. Security and Privacy Controls
- Password hashing via password_hash/password_verify
- CSRF token checks on forms
- Session ID regeneration on auth events
- Role-based route guards (admin vs student)
- Internal directories blocked via .htaccess
- Data minimization and PH Data Privacy Act alignment baseline

## 12. Device and Performance Strategy
- Mobile-first responsive CSS
- Works on Android/iOS browsers and desktop/laptop
- Lightweight asset strategy (single CSS + JS)
- Optional offline extension path: service worker + cached lesson bundles

## 13. Admin Controls Matrix
- Add module
- Add lesson
- Add quiz question
- Send reminder notification
- View analytics dashboard

## 14. Database Design (Implemented)
- users
- quarters
- modules
- lessons
- quiz_questions
- quiz_attempts
- student_lesson_progress
- badges
- student_badges
- notifications
- password_resets

## 15. Testing and Evaluation Plan
- Functional test: register/login/reset/quiz/admin CRUD
- Security test: CSRF validation and access controls
- Usability test: 5-10 SHS students (ease, clarity, engagement)
- Bug tracking: route failures, score mismatch, broken form flow
- Learning impact metric: pre-test vs post-test delta

## 16. Delivery Phases
1. Foundation and installer
2. Auth + role guards
3. Curriculum and quiz engine
4. Gamification and reminders
5. Analytics and admin controls
6. QA and MELCs content validation

## 17. Future Enhancements
- Interactive graph/simulation components
- AI hint generator
- Voice narration
- English/Filipino language toggle
- Dark mode
- Printable worksheets
- Optional offline service worker caching
