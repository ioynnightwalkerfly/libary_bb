# University Library Borrowing System Design (PHP + XAMPP)

## 1. Project Overview
This document provides a technology-specific blueprint for a university library borrowing platform implemented with **PHP 8**, **HTML5**, **CSS3**, and **vanilla JavaScript**, running on the **XAMPP** stack (Apache + MySQL/MariaDB + PHP). The design covers the end-user borrowing flow, librarian administration tools, and supporting infrastructure required to deliver a secure, maintainable system deployable in an on-premise university environment.

## 2. Technology Stack & Environment
| Layer | Choice | Notes |
| --- | --- | --- |
| Web Server | Apache (bundled with XAMPP) | Configure virtual host (e.g., `library.local`) pointing to project `public/` directory. |
| Backend | PHP 8.x | Object-oriented PHP with MVC separation. Recommend Composer for autoloading (PSR-4). |
| Database | MySQL 8 / MariaDB (via XAMPP) | Enable InnoDB, enforce foreign keys, utf8mb4 collation. |
| Frontend | HTML5, CSS3 (SCSS optional), JavaScript (ES6) | Use modular JS for interactivity; optional lightweight framework (e.g., Alpine.js) if needed. |
| Sessions/Auth | PHP native sessions + JWT (for AJAX APIs) | Store session IDs in secure cookies (HttpOnly, SameSite=Lax). |
| Tooling | Composer, npm (for asset bundling), PHPUnit | PHPUnit for backend tests, ESLint/Prettier optional for JS. |

### 2.1 Local Setup (XAMPP)
1. Install XAMPP and ensure Apache & MySQL services are running.
2. Create a database named `university_library` via phpMyAdmin or MySQL CLI.
3. Configure `.env` (or `config/database.php`) with DB credentials (`DB_HOST=localhost`, `DB_USER=root`, etc.).
4. Place the project inside the XAMPP `htdocs/` directory (e.g., `C:/xampp/htdocs/library-system`).
5. Run `composer install` (if using Composer-based structure) and `npm install && npm run build` for frontend assets.
6. Visit `http://library.local/` (configure hosts file & Apache vhost) or `http://localhost/library-system/public/`.

## 3. Core Modules & User Roles
### 3.1 General Users (Students / Faculty)
1. **Registration & Authentication**
   - Register with university email or student/faculty ID; email verification optional.
   - Login form with session-based authentication; password hashing via `password_hash()`.
2. **Book Discovery**
   - Search by title, author, category, ISBN, or keywords; filter by availability.
   - View book details (cover, summary, available copies, shelf location).
3. **Borrowing Workflow**
   - Select available copy; choose permitted loan duration (7/14/30 days per policy).
   - Confirm to generate unique borrow code (e.g., `BRW-20241005-0001`) and optional QR.
   - Display confirmation modal + email receipt.
4. **User Dashboard**
   - View active loans (borrow date, due date, status, remaining days).
   - View past loans, renewal eligibility, outstanding fines.
5. **Return & Renewal**
   - Submit borrow code or scan QR (using webcam + JS) to mark return.
   - Request renewal if allowed and copy not reserved; update due date accordingly.

### 3.2 Administrators / Librarians
1. **Authentication**: Admin login with role-based access control (RBAC).
2. **Book Inventory**: CRUD operations on books, cover uploads (stored in `/storage/covers`).
3. **Loan Oversight**: Dashboard summarizing active, overdue, and returned loans; filters by user/book/date.
4. **Notifications**: Configure email templates, send reminders for due/overdue loans.
5. **User Management**: View users, adjust borrowing limits, suspend accounts, reset passwords.
6. **Reports**: Export CSV/PDF (e.g., monthly circulation, overdue list).

## 4. Application Architecture (MVC)
```
/ (project root)
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/          # Business logic (loan service, notification service)
│   ├── Repositories/      # Database abstraction using PDO
│   ├── Middlewares/
│   └── Helpers/
├── public/
│   ├── index.php          # Front controller, bootstraps app
│   ├── assets/css/
│   ├── assets/js/
│   └── uploads/covers/
├── resources/
│   ├── views/             # Blade-like templates or plain PHP views
│   └── emails/
├── config/
│   ├── app.php
│   └── database.php
├── routes/
│   ├── web.php            # Web routes (views)
│   └── api.php            # AJAX endpoints (JSON)
├── storage/
│   ├── logs/
│   └── cache/
└── tests/
```

- **Routing**: Use a lightweight router (e.g., FastRoute) or mini-framework (Slim/Lumen). Map HTTP requests to controllers.
- **Models**: Use PDO with prepared statements; optional ORM (Eloquent standalone) if desired.
- **Views**: PHP templates with layout inheritance; integrate Bootstrap/Tailwind for styling.
- **Services**: Encapsulate borrow logic (loan creation, renewal, return, code generation).
- **Middlewares**: Authentication guard, CSRF protection, role checks.

### 4.1 Component Interaction Flow
1. Request hits `public/index.php` → router resolves controller.
2. Controller validates input, calls service methods.
3. Services call repositories for DB operations (transactions handled via PDO).
4. Response rendered via view template or JSON payload for AJAX.
5. Background tasks (cron) handle overdue reminders and cleanup.

## 5. Database Schema (MySQL)
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    university_id VARCHAR(32) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'faculty', 'librarian', 'admin') NOT NULL DEFAULT 'student',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    max_loans TINYINT UNSIGNED NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category_id INT UNSIGNED,
    description TEXT,
    language VARCHAR(50),
    publisher VARCHAR(100),
    publication_year YEAR,
    cover_image_path VARCHAR(255),
    total_copies SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    available_copies SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    location_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE book_copies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    copy_code VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('available', 'borrowed', 'reserved', 'maintenance') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_copies_book FOREIGN KEY (book_id) REFERENCES books(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    borrow_code VARCHAR(50) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    book_copy_id INT UNSIGNED NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
    renewed_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_loans_copy FOREIGN KEY (book_copy_id) REFERENCES book_copies(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'pending',
    queued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at TIMESTAMP NULL,
    CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reservation_book FOREIGN KEY (book_id) REFERENCES books(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    loan_id INT UNSIGNED,
    channel ENUM('email', 'sms', 'web') NOT NULL DEFAULT 'email',
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'queued', 'failed') NOT NULL DEFAULT 'sent',
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notifications_loan FOREIGN KEY (loan_id) REFERENCES loans(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5.1 Stored Procedures & Views (Optional)
- **Stored procedure** `sp_mark_overdue_loans` to update overdue statuses nightly.
- **View** `vw_active_loans` summarizing user loans for dashboards.

## 6. Web Routes & API Endpoints
| Route | Method | Purpose | Middleware |
| --- | --- | --- | --- |
| `/` | GET | Landing page, highlights catalog | Guest |
| `/login` | GET/POST | User login | Guest/Auth throttle |
| `/register` | GET/POST | User registration | Guest/CSRF |
| `/dashboard` | GET | User dashboard (active loans) | Auth:user |
| `/books` | GET | Book listing/search (AJAX for filters) | Auth optional |
| `/books/{id}` | GET | Book detail page | Auth optional |
| `/borrow` | POST | Submit borrow request | Auth:user + CSRF |
| `/borrow/{code}/receipt` | GET | Borrow confirmation | Auth:user |
| `/return` | POST | Return by code / QR | Auth:user |
| `/renew/{loanId}` | POST | Request renewal | Auth:user |
| `/admin` | GET | Admin dashboard | Auth:admin |
| `/admin/books` | GET/POST/PUT/DELETE | Manage books (AJAX) | Auth:admin + CSRF |
| `/admin/loans` | GET | Loan management grid | Auth:admin |
| `/admin/users` | GET/PUT | Manage user accounts | Auth:admin |
| `/admin/reports/export` | GET | Export CSV/PDF | Auth:admin |
| `/api/notifications/send` | POST | Trigger reminder (AJAX) | Auth:admin + CSRF |

- **CSRF protection** via hidden tokens in forms and `X-CSRF-TOKEN` header for AJAX.
- **Validation** handled through controller validation classes to sanitize input.

## 7. Frontend UI Structure
### 7.1 Layout & Styling
- Base layout with header (logo, nav, login status), sidebar (for admin), and content area.
- Use CSS utility framework (Bootstrap 5 or Tailwind) compiled via npm.
- Responsive tables (DataTables or custom) for catalog and admin lists.

### 7.2 Key Pages
1. **Login/Register (`auth/login.php`, `auth/register.php`)**
   - Form validation feedback, "Remember me" option, forgot password link.
2. **Catalog (`books/index.php`)**
   - Search bar, filters (category, availability), card/list toggle.
   - "Borrow" button triggers modal (AJAX) to choose duration.
3. **Borrow Confirmation Modal**
   - Displays borrow code, due date, QR image (generated server-side using library like `endroid/qr-code`).
4. **User Dashboard (`user/dashboard.php`)**
   - Tabs: Active Loans, Loan History, Reservations.
   - Alerts for upcoming due dates (progress bar showing days left).
5. **Return Page (`user/return.php`)**
   - Input for borrow code, optional QR scan component (JS `getUserMedia`).
6. **Admin Dashboard (`admin/index.php`)**
   - Metrics cards (total books, active loans, overdue count).
   - Table widgets for quick review.
7. **Admin Inventory (`admin/books.php`)**
   - CRUD forms with image upload (validate file type/size, store in `/public/uploads/covers`).
8. **Admin Loans (`admin/loans.php`)**
   - Filterable table with actions: mark returned, extend due date, view history.
9. **Admin Users (`admin/users.php`)**
   - Manage roles, reset password, deactivate/reactivate.

## 8. Security Considerations
- Enforce HTTPS in production; set `session.cookie_secure` and `session.cookie_samesite=Lax`.
- Use `password_hash()` / `password_verify()`; require strong passwords.
- Parameterized queries with PDO to avoid SQL injection.
- Implement CSRF tokens on forms; sanitize all output with `htmlspecialchars()`.
- Limit file upload types for cover images; scan size and sanitize filenames.
- Apply rate limiting on login, borrow, and return endpoints.
- Log all admin actions in `audit_logs`.
- Schedule cron job for overdue reminders and data integrity checks.

## 9. Notification & Background Jobs
- Use PHP CLI scripts triggered via Windows Task Scheduler / cron (if on Linux) to:
  - Run `php artisan schedule:run` (if using Laravel) or custom scripts for overdue reminders.
  - Generate daily/weekly reports and email them to librarians.
- Email integration via SMTP (university mail server or transactional provider).
- Optional SMS integration using third-party API (Twilio, etc.).

## 10. Implementation Roadmap
1. **Foundation**: Initialize project structure, configure Composer autoload, create `.env` and config files.
2. **Auth Module**: Build registration, login, password reset, and role middleware.
3. **Catalog Module**: Implement book listing/search, detail views, admin CRUD.
4. **Borrowing Module**: Create loan service for borrow, renew, return flows; generate borrow codes.
5. **User Dashboard**: Display active loans, history, reservations with responsive UI.
6. **Admin Panel**: Build dashboards, loan oversight, user management, reports.
7. **Notifications**: Implement email templates and reminder scheduler.
8. **Testing & QA**: PHPUnit for services/repositories, browser-based testing for UI workflows.
9. **Deployment**: Configure production Apache vhost, optimize assets, back up database.

## 11. Prompt (English) for Prototyping Tools
> "Design and prototype a university library book borrowing system built with PHP 8 on the XAMPP stack. Include user login/registration, book search and borrowing with configurable durations, unique borrow-code confirmation (with optional QR), user dashboards showing current loans and due dates, and return workflows by code or QR scan. Provide an admin panel for inventory management, borrowing records, overdue alerts, and user management. Ensure the UI is responsive using HTML5, CSS3, and JavaScript, and all data persists in a MySQL database."