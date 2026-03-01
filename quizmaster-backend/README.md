# QuizMaster — Backend (Laravel API)

REST API built with **Laravel 12** and **MySQL**. Provides authentication, quiz management, subject organization, and reporting.

## Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 |
| Database | MySQL 8.0+ |
| Auth | Laravel Sanctum (token-based) |
| PDF export | barryvdh/laravel-dompdf |
| Excel export | maatwebsite/excel |

## Setup

See [`../SETUP.md`](../SETUP.md) for full installation instructions.

```bash
# Quick start
composer install
cp .env.example .env
php artisan key:generate
# configure DB in .env, then:
php artisan migrate
php artisan db:seed
php artisan serve
```

## API Endpoints

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register user |
| POST | `/api/auth/login` | User login → token |
| POST | `/api/admin/login` | Admin login → token |
| GET | `/api/subjects` | List active subjects with question counts |

### User (requires `Authorization: Bearer <token>`)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/me` | Current user info |
| PUT | `/api/auth/profile` | Update name/email |
| PUT | `/api/auth/password` | Change password |
| POST | `/api/auth/logout` | Revoke token |
| POST | `/api/quiz/start` | Start or resume quiz (`subject_id` optional) |
| GET | `/api/quiz/history` | Paginated attempt history |
| GET | `/api/quiz/{code}` | Resume/load attempt |
| POST | `/api/quiz/{code}/answer` | Save/lock an answer |
| POST | `/api/quiz/{code}/finish` | Submit quiz |
| GET | `/api/quiz/{code}/result` | Full result with correct answers |
| GET | `/api/quiz/{code}/report/pdf` | Download PDF report |
| GET | `/api/quiz/{code}/report/excel` | Download Excel report |

### Admin (requires admin token)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/me` | Current admin info |
| POST | `/api/admin/logout` | Revoke admin token |
| GET/POST | `/api/admin/subjects` | List / create subjects |
| PUT | `/api/admin/subjects/{id}` | Update subject |
| DELETE | `/api/admin/subjects/{id}` | Delete subject (blocked if has questions) |
| PUT | `/api/admin/subjects/{id}/toggle-active` | Toggle active status |
| GET/POST | `/api/admin/questions` | List (filterable) / create questions |
| PUT | `/api/admin/questions/{id}` | Update question |
| DELETE | `/api/admin/questions/{id}` | Delete question |
| POST | `/api/admin/questions/import-json` | Bulk import from JSON |
| GET | `/api/admin/users` | List users |
| GET | `/api/admin/users/{id}` | User detail |
| PUT | `/api/admin/users/{id}/toggle-active` | Activate/deactivate user |
| GET | `/api/admin/users/{id}/attempts` | User's attempt history |
| GET | `/api/admin/reports/summary` | Stats summary |
| GET | `/api/admin/reports/attempts` | All attempts (filterable) |
| GET | `/api/admin/reports/questions/analysis` | Per-question stats |
| GET | `/api/admin/reports/export/excel` | Export report as Excel |
| GET | `/api/admin/reports/export/pdf` | Export report as PDF |

## Key Files

```
app/
  Http/Controllers/
    QuizController.php           # Start, answer, finish, result
    SubjectController.php        # Public subject list
    Admin/
      SubjectController.php      # Admin subject CRUD
      QuestionController.php     # Admin question CRUD
      UserController.php
      ReportController.php
  Models/
    Subject.php
    Question.php
    QuizAttempt.php
    QuizAnswer.php
    User.php / Admin.php
  Services/
    QuizService.php              # Core quiz logic
    ReportService.php
database/
  migrations/
  seeders/
    SubjectSeeder.php            # Seeds 7 subjects + assigns questions
    QuestionSeeder.php
    AdminSeeder.php
    UserSeeder.php
```

## Business Rules

- **Subject quiz:** all active questions in the subject are used (min 1)
- **All Subjects quiz:** 100 random questions from the full active pool (min 100)
- Correct answers are **never** returned during an active quiz
- In-progress attempts auto-resume instead of creating a new one
- Pass mark: 50% of total questions
