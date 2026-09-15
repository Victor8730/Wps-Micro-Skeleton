# Changelog

## Unreleased

- Require the stable WPS Micro 3.1 framework and commit a PHP 8.3-compatible dependency lock.
- Add named routes, protected route groups, and the route:list console command.
- Add private message CRUD with owner-scoped queries, pagination, CSRF protection, and validation.
- Preserve home_messages and historical migrations; add ownership through a new migration.
- Add shared Twig fields, flash messages, pagination, and an account layout.
- Verify that authenticated sessions still reference an existing user.
- Add app:setup, app:check, and opt-in db:seed commands without default account credentials.
- Add PHPStan, formatting checks, browser-workflow tests, and MariaDB migration CI.
- Serve public assets correctly when using PHP's built-in development server.

The store application is intentionally not included in the universal skeleton.
