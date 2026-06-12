# San Andreas Crime Map

This is a PHP and MySQL final project. It uses the same basic style as the course examples: each PHP page starts by including `config.php`, handles its database work at the top, then shows the HTML page below.

The project has login, register, sessions, user roles, a live map, an admin dashboard, and MySQL tables for users, incidents, and dispatches.

## How To Run

1. Start Apache and MySQL with XAMPP, WAMP, or Laragon.
2. Import `database/schema.sql` into MySQL.
3. Check the database name, username, and password in `config.php`.
4. Open:

```text
http://localhost/php-final/index.php
```

## Database Login

The SQL file creates this admin account:

```text
Email: admin@crimemap.com
Password: admin123
```

You can also run `create-admin.php` to create another admin account:

```text
Email: admin@crimemap.local
Password: Admin@2026
```

## Main Files

`config.php` connects to MySQL with PDO and starts the session. Every PHP page includes this file first.

`index.php` checks the session. Admin users go to `admin/dashboard.php`, normal users go to `map.php`, and guests go to `auth/login.php`.

`auth/login.php` shows the login form and also handles login POST requests. It finds the user by email, checks the password with `password_verify`, saves the user in `$_SESSION['user']`, then sends back the correct redirect.

`auth/register.php` shows the register form and also handles register POST requests. It validates the name, email, password, and confirm password, checks if the email already exists, hashes the password, saves the user, and logs the user in.

`auth/logout.php` clears the session and sends the user back to the login page.

`auth/forgot-password.php` is only a placeholder page. It does not send real password reset emails yet.

`map.php` is the normal user page. It loads the Leaflet map, shows incident markers, lets users report incidents, and lets admins dispatch or resolve incidents from the map.

`admin/dashboard.php` is the admin CRUD page. It lets admins view, add, edit, delete, filter, search, resolve, and export incidents.

`api.php` is the backend for the map and dashboard JavaScript. It receives an `action` from the URL, runs the matching database query, and returns JSON.

`database/schema.sql` creates the database tables and sample records.

`assets/css` contains the page styling.

`assets/js` contains the browser-side form, map, and dashboard behavior.

## Database Tables

`users` stores login accounts. The important fields are `email`, `password_hash`, `full_name`, and `role`.

`incidents` stores reports shown on the map. It has the title, description, latitude, longitude, severity, status, and incident type.

`dispatches` stores response units sent to incidents. It is connected to `incidents` with `incident_id`.

## How Login Works

1. The user opens `auth/login.php`.
2. The form is submitted by `assets/js/auth.js`.
3. The same PHP file receives the email and password.
4. PHP selects the user from the `users` table.
5. `password_verify` checks the typed password against `password_hash`.
6. The logged-in user is saved in `$_SESSION['user']`.
7. Admins are sent to the dashboard. Viewers are sent to the map.

## How Register Works

1. The user opens `auth/register.php`.
2. The form is submitted by `assets/js/auth.js`.
3. PHP validates the fields.
4. PHP checks if the email already exists.
5. The password is hashed with `password_hash`.
6. The new user is inserted into `users`.
7. The user is saved in the session and sent to `map.php`.

## How The Map Works

1. `map.php` loads the Leaflet map and `assets/js/map.js`.
2. JavaScript calls `api.php?action=get_incidents`.
3. `api.php` selects incidents from MySQL and returns JSON.
4. JavaScript turns each incident into a map marker and a list item.
5. When a user reports an incident, JavaScript sends the data to `api.php?action=create_incident`.
6. PHP inserts the new incident into MySQL.
7. JavaScript reloads the incident list and marker list.

## How The Admin Dashboard Works

1. `admin/dashboard.php` checks that the logged-in user has the `admin` role.
2. `assets/js/dashboard.js` loads incidents from `api.php?action=get_incidents`.
3. The dashboard displays the rows in a table.
4. Add and edit actions send data to `create_incident` or `update_incident`.
5. Delete actions send the incident ID to `delete_incident`.
6. Resolve actions update the incident status to `resolved`.

## API Actions

`get_incidents` returns all incidents.

`create_incident` adds a new incident.

`update_incident` edits an existing incident. Admin only.

`delete_incident` deletes an incident. Admin only.

`dispatch_incident` changes the incident status to `dispatched` and creates a row in `dispatches`.

`resolve_incident` changes the incident status to `resolved` and marks related dispatches as completed.

## Course-Style Structure

The PHP files are intentionally written close to the course examples:

- include `config.php` at the top
- read `$_POST`, `$_GET`, or JSON input
- validate variables before using them
- prepare SQL with `$pdo->prepare`
- execute SQL with values
- redirect or return JSON
- keep explanations in this README instead of comments in code
