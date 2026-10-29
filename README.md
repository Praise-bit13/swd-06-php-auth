# SWD Auth

A PHP 8 and MySQL authentication example for XAMPP. The default database name is `swd_auth`.

## Setup

1. Import [schema.sql](schema.sql) in phpMyAdmin for a fresh database.
2. For an existing Stage 1 database, apply [stage2_migration.sql](stage2_migration.sql) and then [stage3_migration.sql](stage3_migration.sql). Run each migration once, in order.
3. Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` for your MySQL instance. The defaults are `127.0.0.1:3306`, database `swd_auth`, and XAMPP's default `root` account with a blank password.
4. Open `http://localhost/swd/06-php-auth/register.php` to create an account. The application defaults to `APP_ENV=development`; configure `APP_ENV=production` in the web server environment before deploying.

The database account used by a deployed application should have only the database permissions it needs. Do not expose `config/config.php` or database credentials publicly.

## Make the first administrator

After registering the account, open a terminal in this project folder and run:

```powershell
C:\xampp\php\php.exe promote-admin.php me@example.com
```

The promotion utility refuses web requests and updates only an existing account.

## Security features

- **Passwords:** Stored using PHP `password_hash()` with `PASSWORD_DEFAULT`, verified with `password_verify()`, and upgraded with `password_needs_rehash()`. Passwords are never stored or displayed in plain text.
- **Database access:** PDO prepared statements are used for queries containing user-supplied values.
- **Sessions:** Strict, cookie-only sessions use `SWDSESSID`, `HttpOnly`, `SameSite=Lax`, and session ID regeneration after authentication and password changes. Sessions expire after 30 minutes of inactivity.
- **Transport security:** Development mode permits local HTTP. Any non-development environment requires HTTPS and sends the session cookie with `Secure`. Configure TLS at the web server or trusted TLS-terminating proxy, and ensure the PHP server environment correctly indicates HTTPS; the app deliberately does not trust arbitrary forwarded headers. Configure HSTS at the HTTPS server/proxy.
- **Response headers:** Every PHP response uses `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: strict-origin-when-cross-origin`.
- **CSRF:** State-changing forms use session-bound tokens checked with `hash_equals()`. Logout and user role changes are POST-only.
- **Output escaping:** Dynamic HTML output is escaped with `htmlspecialchars()` through `e()`.
- **Authentication and throttling:** Invalid credentials use the same message whether the email or password is wrong. Failed login attempts are counted by email and IP, with a 15-minute window and a five-attempt threshold.
- **Authorization:** Protected pages verify the current user, and administrator routes check the user's current database role. The admin interface prevents an administrator from demoting their own account.
- **Redirects:** Post-login intended URLs are restricted to safe, local relative paths.
- **Errors:** Detailed PHP errors are displayed only in development mode. Use `APP_ENV=production` outside local development.

## Account management

After signing in, use **Profile** in the navigation to update your name/email or change your password. Changing a password requires the current password and regenerates the session ID.

Administrators can use **Admin** to review accounts and promote or demote other users.
