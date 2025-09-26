"# libary_bb" 
# University Library Borrowing System

This project delivers a working PHP 8 application for managing book loans in a university library. It targets the XAMPP stack (Apache, PHP, MySQL) with the database named **`libary_bb`**.

## Getting started

1. **Clone the project** into the XAMPP `htdocs` directory (or configure a virtual host pointing to the `public/` folder).
2. **Import the schema**: open phpMyAdmin or the MySQL CLI and execute [`database/schema.sql`](database/schema.sql). The script creates tables, seed categories/books, and an administrator account.
3. **Configure credentials** in [`public/config.php`](public/config.php) if your MySQL username/password differ from the XAMPP defaults.
4. **Browse to** `http://localhost/libary_bb/public/` (adjust path if using a virtual host).

Default administrator account:

| Email | Password |
| --- | --- |
| `admin@university.edu` | `AdminPass123!` |

## Application structure

```
public/
├── assets/css/styles.css      # Shared styling
├── config.php                 # Database connection helper
├── includes/                  # Shared utilities, layout, auth helpers
├── *.php                      # Feature pages (login, dashboards, admin tools, etc.)
database/
└── schema.sql                 # MySQL DDL + seed data for libary_bb
docs/
└── library_system_design.md   # Detailed functional/technical design notes
```

Key features implemented:

* Student and staff registration/login with hashed passwords and session-based authentication
* Searchable book catalogue with category filtering and borrow duration selection
* Borrow confirmation that issues a borrow code and updates inventory automatically
* Personal dashboard summarising active loans, overdues, and history (including return workflow via borrow code)
* Administrator console for managing inventory, users (suspension + password reset), and auditing borrowing records/overdue items

For deeper architectural guidance or to extend the system, refer to [`docs/library_system_design.md`](docs/library_system_design.md).