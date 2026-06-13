# San Andreas Crime Map

This is my PHP and MySQL final project. It is a small incident reporting app with login/register, a live map, and an admin dashboard for managing reports.

The project uses plain PHP files instead of a framework. Most pages include `public/config.php` first, do any needed PHP/database work near the top, and then render the HTML below it.

## How to run it

1. Start Apache and MySQL with XAMPP, WAMP, or Laragon.
2. Import `database/schema.sql` into MySQL.
3. Check the database settings in `public/config.php`.
4. Open this in the browser:

```text
http://localhost/php-final/public/index.php
```

## Admin login

The database import creates this admin account:

```text
Email: admin@crimemap.com
Password: admin123
```

There is also a helper file at `public/create-admin.php` if another admin account is needed.

## Main files

- `public/config.php` starts the session and connects to MySQL with PDO.
- `public/index.php` sends logged-in users to the right page.
- `public/auth/login.php` handles the login page and login request.
- `public/auth/register.php` handles new user registration.
- `public/auth/logout.php` logs the user out.
- `public/auth/forgot-password.php` is only a placeholder page.
- `public/map.php` is the normal user map page.
- `public/admin/dashboard.php` is the admin dashboard.
- `public/api.php` is the JSON backend used by the map and dashboard.
- `database/schema.sql` creates the tables and sample records.

## What it does

Users can register, sign in, view incidents on a Leaflet map, and report a new incident with a type, severity, description, and coordinates.

Admins can use the dashboard to view incidents, add new ones, edit them, delete them, mark them resolved, dispatch response units, search/filter the table, and export records to CSV.

## Database tables

The project uses three tables:

- `users` for login accounts and roles
- `incidents` for map reports
- `dispatches` for response units connected to incidents

The main CRUD work is for the `incidents` table.

## API actions

`public/api.php` uses the `action` query string to decide what to do:

- `get_incidents`
- `create_incident`
- `update_incident`
- `delete_incident`
- `dispatch_incident`
- `resolve_incident`

The PHP uses prepared PDO statements for the database queries.

## Notes

This is built in a simple course-project style on purpose. The PHP is kept readable and direct instead of being split into a full MVC structure.
