# AI-Powered Construction & Project Management System

A comprehensive, AI-enhanced backend application built with Laravel for the construction industry.

---

## Overview

This project provides a robust RESTful API to manage the complete lifecycle of construction projects. It facilitates collaboration between company administrators, project managers, assistants, engineers, and project owners. The system handles everything from project spaces and work items to equipment maintenance, document versioning, labor cost tracking, and incorporates AI for site inspection and visualization.

---

## Features

- ✅ **Authentication & Authorization**: Secure API access using Laravel Sanctum and role-based access control (Spatie).
- ✅ **Role Management**: Specialized access for Company Admins, Project Managers, Assistants, Engineers, and Project Owners.
- ✅ **Project & Space Tracking**: Manage multiple projects and their specific spaces (e.g., ceramic, gypsum, sanitary).
- ✅ **Work Item Management**: Track progress, reorder tasks, and handle approval workflows for progress updates.
- ✅ **Document Versioning**: Upload, version, and download project documents safely.
- ✅ **Equipment Management**: Track equipment status, schedule maintenance, and handle equipment bookings.
- ✅ **Material & Invoice Tracking**: Manage materials, track labor costs, and generate/archive invoices.
- ✅ **AI Integrations**: Generate AI visualizations, analyze construction progress, and provide automated site inspections.
- ✅ **PDF Generation**: Automatically generate and export contracts and reports.
- ✅ **Push Notifications**: Real-time alerts using Firebase Cloud Messaging (FCM).
- ✅ **Weather Integration**: Fetch real-time weather data for project sites.
- ✅ **REST API**: Fully structured API for seamless frontend integration.

---

## Tech Stack

| Category | Technology |
|----------|------------|
| Backend | PHP 8.2, Laravel 12.0 |
| Authentication | Laravel Sanctum, Spatie Permissions |
| Database | SQLite (Default), MySQL, PostgreSQL supported |
| AI Integration | Google Gemini, OpenAI |
| Notifications | Firebase Cloud Messaging (Kreait) |
| PDF Generation | DomPDF, Snappy |
| HTTP Client | Guzzle |
| Testing | PHPUnit |

---

## Project Structure

```text
├── app/
│   ├── Http/Controllers/   # API request handling and business logic endpoints
│   ├── Models/             # Eloquent database models
│   └── Services/           # Encapsulated business logic (Service Pattern)
├── bootstrap/              # Framework bootstrapping
├── config/                 # Application configuration (DB, Auth, Services, etc.)
├── database/
│   ├── factories/          # Model factories for testing
│   ├── migrations/         # Database schema definitions
│   └── seeders/            # Database seeder scripts
├── public/                 # Public entry point (index.php) and assets
├── resources/              # Uncompiled assets and views
├── routes/
│   └── api.php             # REST API route definitions
├── storage/                # File storage, logs, and framework cache
└── tests/                  # PHPUnit feature and unit tests
```

---

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM (for Vite assets)
- A supported database (SQLite, MySQL, etc.)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/AbdAlrahmanAlsaadi/graduation.git
   cd graduation
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Install NPM Dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```
   *Edit the `.env` file to configure your database connection, AI keys, and mail settings.*

5. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

6. **Database Setup**
   Ensure your database is created (or use the default SQLite), then run migrations:
   ```bash
   php artisan migrate
   ```

7. **Start the Development Server**
   ```bash
   php artisan serve
   ```
   *For asset compilation and queue listening during development:*
   ```bash
   npm run dev
   ```

---

## Configuration

Below are the key environment variables documented from `.env.example`:

| Variable | Description |
|----------|-------------|
| `APP_NAME` | The name of the application (e.g., Laravel). |
| `APP_ENV` | Environment type (local, production, testing). |
| `APP_KEY` | Secret key used for encryption. |
| `APP_DEBUG` | Enable/disable debug mode. |
| `APP_URL` | Base URL of the application. |
| `DB_CONNECTION` | Database driver (e.g., sqlite, mysql, pgsql). |
| `SESSION_DRIVER` | How sessions are stored (database by default). |
| `QUEUE_CONNECTION` | Backend for queue processing (database). |
| `MAIL_MAILER` | Mail driver used (log by default). |
| `AWS_ACCESS_KEY_ID` | Amazon S3 access key (if using S3 for storage). |
| `VITE_APP_NAME` | App name accessible to the Vite frontend. |

*(Note: Ensure you add your OpenAI, Gemini, and Firebase credentials to the `.env` if utilizing AI/FCM features).*

---

## Usage

### Example API Request (Login)
```bash
curl -X POST http://localhost:8000/api/auth/internal/login \
     -H "Content-Type: application/json" \
     -d '{"email": "admin@example.com", "password": "password123"}'
```

### Response
```json
{
    "token": "1|abcdef123456...",
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com"
    }
}
```

Use the returned token as a Bearer token in the `Authorization` header for protected routes.

---

## API Documentation

The project exposes a rich set of API endpoints. A Postman collection (`APiGraduation.postman_collection.json`) is included in the repository for detailed endpoint interaction.

**Key API Modules:**

| Endpoint Base | Method(s) | Description | Authentication |
|---------------|-----------|-------------|----------------|
| `/api/auth/company/login` | POST | Company level authentication | No |
| `/api/auth/internal/login` | POST | Staff/Internal authentication | No |
| `/api/projects` | GET, POST, PUT, DEL | Manage construction projects | Yes (Sanctum) |
| `/api/documents` | POST, GET | Upload and view project documents | Yes (Sanctum) |
| `/api/equipment` | POST, GET, DEL | Manage and book equipment | Yes (Sanctum) |
| `/api/materials` | GET, POST, PUT, DEL | Manage inventory and materials | Yes (Sanctum) |
| `/api/ai-visualization` | POST | Generate AI visuals for spaces | Yes (Sanctum) |
| `/api/projects/{id}/weather/today` | GET | Fetch site weather info | Yes (Sanctum) |

---

## Database

- **Database Type**: Relational (Configured for SQLite by default, supports MySQL/MariaDB/PostgreSQL).
- **ORM**: Eloquent ORM.
- **Main Entities**:
  - `User`: Handles authentication and roles.
  - `Project`: Core entity tying together spaces, work items, and documents.
  - `Space`: Distinct physical areas within a project (e.g., specific rooms).
  - `WorkItem`: Specific tasks associated with a project.
  - `Equipment` & `Material`: Resources used on site.
  - `Document`: Files associated with projects (supports versioning).
- **Relationships**: A `Project` has many `Spaces` and `WorkItems`. A `WorkItem` can have many `Materials`, `Invoices`, and `ProgressUpdateRequests`.

---

## Architecture

The software architecture follows the **MVC (Model-View-Controller)** pattern extended with a **Service Layer**:
- **Controllers** (`app/Http/Controllers`): Handle incoming API requests, validate input, and return JSON responses.
- **Services** (`app/Services`): Encapsulate complex business logic (e.g., `WorkItemProgressService`, `ProjectService`, `AgnesService`, `NotificationService`).
- **Models** (`app/Models`): Represent data and database relationships.

This provides a clean separation of concerns, keeping controllers thin and business logic reusable.

---

## Security

- **Authentication**: Stateless API authentication via Laravel Sanctum tokens.
- **Authorization**: Spatie Laravel Permissions handles role-based and permission-based access control directly on API routes via middleware.
- **Password Hashing**: Secure password storage using bcrypt.
- **Data Validation**: Strict input validation using Laravel Form Requests and controller-level validation.

---

## Testing

- **Test Framework**: PHPUnit.
- **Running Tests**:
  ```bash
  php artisan test
  ```
- **Coverage**: The repository includes directories for both `Feature` (integration) and `Unit` tests.

---

## Deployment

Standard PHP/Laravel deployment methods are supported:
- **Cloud Providers**: AWS, DigitalOcean, Linode.
- **PaaS**: Laravel Forge, Vercel, Heroku.
*(Note: Docker configuration files were not detected in the codebase).*

To prepare for production:
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Performance

- **Caching**: Configured to use database caching by default, extensible to Redis/Memcached.
- **Queues**: Database queue connection configured for background tasks, improving API response times for heavy tasks (like AI processing or sending notifications).
- **Lazy Loading**: Eloquent relationships are utilized efficiently, preventing N+1 query issues through eager loading where appropriate.

---

## Development

Contributions are welcome!
- Ensure adherence to PSR-12 coding standards.
- Write feature tests for any new API endpoints.
- Ensure all business logic is placed inside `app/Services` rather than controllers.

---

## Troubleshooting

- **500 Server Error on fresh install**: Ensure `.env` is properly configured, `php artisan key:generate` has been run, and directory permissions for `storage` and `bootstrap/cache` are writeable.
- **Missing tables**: Run `php artisan migrate:fresh --seed` to recreate the database.
- **CORS Issues**: Check the `config/cors.php` configuration if requests from the frontend are blocked.

---

## License

This project utilizes the Laravel framework, which is open-sourced software licensed under the MIT license. (Explicit license file not detected in the codebase).

---

## Acknowledgments

Built using [Laravel](https://laravel.com), [Tailwind CSS](https://tailwindcss.com/), [Kreait Firebase](https://github.com/kreait/laravel-firebase), and APIs from [OpenAI](https://openai.com) and [Google Gemini](https://deepmind.google/technologies/gemini/).
