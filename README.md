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

- `config` has database, app, and session setup.
- `database` has `schema.sql` for creating the database tables.
- `public` has the main `index.php`, CSS, JavaScript, and API entry point.
- `src/App` has the PHP classes for login, users, middleware, and incidents.
- `views` has the HTML/PHP pages.

## How To Run It

1. Start Apache and MySQL in XAMPP, WAMP, or Laragon.
2. Import `database/schema.sql` into MySQL.
3. Check the database settings in `config/config.php`.
4. Open this URL:

```text
http://localhost/php-final/public/index.php?route=login
```

## Default Admin

The schema creates an admin account:

```text
Email: admin@crimemap.com
Password: admin123
```

## How Login Works

1. The login page is loaded from `views/auth/login.php`.
2. `public/assets/js/auth.js` checks the form.
3. JavaScript sends the email and password to `public/index.php?route=login`.
4. `AuthController` checks the user.
5. `User` gets the user from the database.
6. PHP checks the password with `password_verify`.
7. The user is saved in the session.
8. The app redirects the user to `dashboard` or `map`.

## How Register Works

1. The register page is loaded from `views/auth/register.php`.
2. JavaScript checks the name, email, password, and confirm password.
3. The data is sent to `public/index.php?route=register`.
4. `AuthController` validates the data again.
5. `User` creates the account in MySQL.
6. The password is hashed before it is saved.
7. The new user is logged in and sent to the map.

## Notes

- The forgot password page is only a placeholder right now.
- The project still needs a real password reset system.
- The database must be running before any page can load because `config/config.php` connects to MySQL.
