# San Andreas Crime Map

This is a PHP and MySQL final project. It has login, register, sessions, user roles, a map page, an admin dashboard, and incident data stored in MySQL.

## What It Does

- Lets users register with name, email, and password.
- Lets users log in with email and password.
- Stores passwords as hashes, not plain text.
- Uses PHP sessions to remember who is logged in.
- Sends admin users to the dashboard.
- Sends normal viewer users to the map.
- Has API routes for incidents.
- Uses MySQL tables for users, incidents, and dispatches.

## Main Folders

- `auth` has the login, register, logout, and forgot-password pages.
- `admin` has the admin CRUD dashboard.
- `database` has `schema.sql` for creating the database tables.
- `assets` has the CSS and JavaScript files.
- `config.php` has database connection.
- `api.php` handles AJAX endpoints.
- `map.php` renders the live map.

## How To Run It

1. Start Apache and MySQL in XAMPP, WAMP, or Laragon.
2. Import `database/schema.sql` into MySQL.
3. Check the database settings in `config.php`.
4. Open this URL:

```text
http://localhost/php-final/index.php
```

## Default Admin

The schema creates an admin account:

```text
Email: admin@crimemap.com
Password: admin123
```

## How Login Works

1. The login page is loaded from `auth/login.php`.
2. `assets/js/auth.js` checks the form.
3. JavaScript sends the email and password to `auth/login.php` via POST.
4. `auth/login.php` checks the user in the database directly.
5. PHP checks the password with `password_verify`.
6. The user is saved in the session.
7. The app redirects the user to `admin/dashboard.php` or `map.php`.

## How Register Works

1. The register page is loaded from `auth/register.php`.
2. JavaScript checks the name, email, password, and confirm password.
3. The data is sent to `auth/register.php` via POST.
4. `auth/register.php` validates the data and checks for duplicates.
5. The password is hashed before it is saved in MySQL.
6. The new user is logged in and sent to `map.php`.

## Notes

- The forgot password page is only a placeholder right now.
- The project still needs a real password reset system.
- The database must be running before any page can load because `config/config.php` connects to MySQL.
