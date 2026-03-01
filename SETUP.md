# QuizMaster — Setup Instructions

## Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+
- npm

---

## Backend Setup (Laravel)

### 1. Navigate to backend directory
```bash
cd quizmaster-backend
```

### 2. Configure environment
Edit `.env` and update the database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quizmaster
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Create the MySQL database
```sql
CREATE DATABASE quizmaster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run migrations and seed
```bash
php artisan migrate
php artisan db:seed
```

This seeds:
- 1 admin account
- 7 default subjects (General Knowledge, Mathematics, Science, Technology, History, Geography, Language & Literature)
- 100 sample questions (assigned to subjects by category)
- 3 sample user accounts

### 5. Start the development server
```bash
php artisan serve
# Runs on http://localhost:8000
```

---

## Frontend Setup (React)

### 1. Navigate to frontend directory
```bash
cd quizmaster-frontend
```

### 2. Install dependencies
```bash
npm install
```

### 3. Start the dev server
```bash
npm run dev
# Runs on http://localhost:5173
```

---

## Default Credentials

### Admin Account
- **Email:** admin@quizmaster.com
- **Password:** Admin@1234

### Sample User Accounts
- alice@example.com / Password@123
- bob@example.com / Password@123
- charlie@example.com / Password@123

---

## API Quick Reference

### cURL Examples

#### Register a user
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"Password@123","password_confirmation":"Password@123"}'
```

#### Login as user
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"alice@example.com","password":"Password@123"}'
```

#### Login as admin
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@quizmaster.com","password":"Admin@1234"}'
```

#### List available subjects (public, no auth)
```bash
curl http://localhost:8000/api/subjects \
  -H "Accept: application/json"
```

#### Start a quiz — All Subjects (100 random questions)
```bash
curl -X POST http://localhost:8000/api/quiz/start \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

#### Start a quiz — specific subject
```bash
curl -X POST http://localhost:8000/api/quiz/start \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"subject_id": 2}'
```

#### Submit an answer
```bash
curl -X POST http://localhost:8000/api/quiz/{attempt_code}/answer \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"question_id":1,"selected_choice":"A","time_taken_seconds":15,"is_locked":true}'
```

#### Finish quiz
```bash
curl -X POST http://localhost:8000/api/quiz/{attempt_code}/finish \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  -H "Accept: application/json"
```

#### Get quiz result
```bash
curl http://localhost:8000/api/quiz/{attempt_code}/result \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  -H "Accept: application/json"
```

#### Download PDF report
```bash
curl http://localhost:8000/api/quiz/{attempt_code}/report/pdf \
  -H "Authorization: Bearer YOUR_USER_TOKEN" \
  -o report.pdf
```

#### Admin: List subjects with question counts
```bash
curl http://localhost:8000/api/admin/subjects \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Admin: Create a subject
```bash
curl -X POST http://localhost:8000/api/admin/subjects \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"PHP Programming","description":"PHP language and Laravel framework","is_active":true}'
```

#### Admin: List users
```bash
curl http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Admin: Get summary report
```bash
curl "http://localhost:8000/api/admin/reports/summary?from=2024-01-01&to=2024-12-31" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

#### Admin: Export admin report as Excel
```bash
curl "http://localhost:8000/api/admin/reports/export/excel" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -o admin-report.xlsx
```

#### Admin: Import questions via JSON
```bash
curl -X POST http://localhost:8000/api/admin/questions/import-json \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d @storage/app/example-questions.json
```
*(Note: wrap file content in `{"questions": [...]}` if not already)*

---

## Example JSON Import File

See `quizmaster-backend/storage/app/example-questions.json` for a working example.

Structure:
```json
{
  "questions": [
    {
      "subject_id": 4,
      "question_text": "What does PHP stand for?",
      "choice_a": "Personal Home Page",
      "choice_b": "PHP: Hypertext Preprocessor",
      "choice_c": "Private Hypertext Protocol",
      "choice_d": "Page Hypertext Processor",
      "correct_choice": "B",
      "explanation": "PHP is a recursive acronym for PHP: Hypertext Preprocessor.",
      "difficulty": "easy",
      "category": "Technology"
    }
  ]
}
```

`subject_id` is optional — questions without it are treated as unassigned.

---

## Frontend Pages

| Path | Description |
|------|-------------|
| `/login` | User login |
| `/register` | User registration |
| `/dashboard` | User dashboard + quiz history |
| `/quiz/:attemptCode` | Quiz player |
| `/result/:attemptCode` | Result + answer review |
| `/profile` | Profile settings |
| `/admin/login` | Admin login |
| `/admin/dashboard` | Admin analytics dashboard |
| `/admin/subjects` | Subject management (CRUD) |
| `/admin/questions` | Question bank (CRUD + import) |
| `/admin/users` | User management |
| `/admin/reports` | Reports + export (Excel/PDF) |

---

## Architecture Notes

### Security
- Correct answers are **never** sent to the client during a quiz — only in the result/review endpoint
- Users can only access their own attempt data and reports
- Admin tokens are validated against `App\Models\Admin` tokenable type
- Inactive users cannot login or start quizzes

### Admin Quiz Mode

Admins can take quizzes themselves (e.g. to preview questions or test the experience):

- Click **"Take a Quiz"** on the admin dashboard
- Select a subject → quiz launches in the same quiz player
- Admin attempts are stored with `admin_id` set and `user_id = NULL`
- Admin quiz history is separate from user history
- All quiz rules (timer, locking, scoring) apply identically

### Business Rules

#### Subject-based quizzes
- Students choose a **subject** before starting — or pick **All Subjects**
- Specific subject → quiz loads **all** active questions in that subject (minimum 1 required)
- All Subjects → 100 random questions from the full active pool (minimum 100 required)
- `total_questions` on each attempt reflects the actual number of questions used

#### Scoring
- 60 seconds per question (frontend timer, backend locks on submit)
- Auto-resumes any in-progress attempt when starting a new quiz
- Score: +1 correct, +0 incorrect/unanswered
- Pass mark: 50% (e.g. 50/100 for All Subjects, ≥50% for subject quizzes)
- Unanswered questions when time expires are auto-locked as incorrect

### API Design
- All API routes are prefixed with `/api`
- User auth uses Laravel Sanctum (`Authorization: Bearer <token>`)
- Admin auth uses the same Sanctum tokens but validated against `App\Models\Admin`
- All responses are JSON — always send `Accept: application/json`
