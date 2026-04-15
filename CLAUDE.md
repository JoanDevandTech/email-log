# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is this

WordPress plugin (GPLv2+) that logs all emails sent via `wp_mail()` to a custom DB table (`{prefix}_email_log`). Forked from Sudar Muthu's original Email Log and maintained by Joan Dev & Tech. Current version: 2.0.0. Requires PHP 7.3+, WordPress 4.0+.

## Development

This is a WordPress plugin with no build step. To test, drop the folder into a WP installation's `wp-content/plugins/` and activate. There is no composer.json, no npm, no test suite, and no linter configured.

- **Text domain:** `email-log-joan-dev`
- **DB table:** `{prefix}_email_log` (created on activation via `dbDelta`)
- **DB version tracking:** option `email-log-db`, current version `0.4`
- **Global accessor:** `email_log()` returns the singleton `\EmailLog\Core\EmailLog` instance.

## Architecture

Entry point: `email-log.php` -- defines constants (`EMAIL_LOG_FILE`, `EMAIL_LOG_URL`, `EMAIL_LOG_PATH`, `EMAIL_LOG_URI`), registers a PSR-4-style autoloader (`EmailLogAutoloader`), and bootstraps the plugin via `load_email_log()`.

### Core loading pattern

The plugin uses a **Loadie interface** (`include/Core/Loadie.php`): any class implementing `load()` can be registered via `EmailLog::add_loadie()`. The main class calls each loadie's `load()` on `plugins_loaded` (priority 101). Some loadies (like `UILoader`) are deferred to `init` via `$loadie_init`.

Registered loadies in bootstrap order:
1. `TableManager` -- DB table creation, schema upgrades, all CRUD for the `email_log` table.
2. `EmailLogger` -- hooks into `wp_mail` filter to capture and insert logs; hooks `wp_mail_failed` to mark failures. Also supports BuddyPress emails.
3. `UILoader` -- loads admin pages, settings, dashboard widget (deferred to `init`).
4. `NonceChecker` -- nonce verification for admin actions. Dies with 403 on invalid nonce.
5. `LogListAction` -- handles bulk actions (delete, delete-all), view message AJAX, resend email AJAX.
6. `ExportAction` -- handles CSV export of logs with current filters.
7. `CronManager` -- WP Cron-based auto-deletion of logs older than N days.
8. `AdminCapabilityGiver` -- grants `manage_email_logs` capability to admins on activation.

### Key directories

- `include/Core/DB/` -- `TableManager`: schema DDL (with indexes on `sent_date`, `result`), insert/delete/fetch queries using `$wpdb->prepare()`, stats queries, multisite support.
- `include/Core/` -- `EmailLogger` (the wp_mail hook), `EmailLog` (main plugin class), `AdminCapabilityGiver`, `CronManager`.
- `include/Core/UI/` -- Admin UI: `Page/` (LogListPage, SettingsPage), `ListTable/` (WP_List_Table subclass with status filter and date range), `Component/` (DashboardWidget with stats, AdminUIEnhancer), `Setting/` (settings API wrappers).
- `include/Core/Request/` -- `NonceChecker`, `LogListAction` (bulk delete + view + resend), `ExportAction` (CSV export).
- `include/Util/` -- `helper.php` (utility functions in `EmailLog\Util` namespace: `stringify`, advanced search parsing), `EmailHeaderParser`.
- `assets/` -- CSS and JS for admin UI (jQuery UI datepicker + tabs + tooltip only).

### DB schema (table `email_log`, version 0.4)

| Column | Type |
|---|---|
| id | mediumint(9) PK AUTO_INCREMENT |
| to_email | VARCHAR(500) |
| subject | VARCHAR(500) |
| message | TEXT |
| headers | TEXT |
| attachments | TEXT ('true'/'false' string) |
| sent_date | timestamp (indexed) |
| attachment_name | VARCHAR(1000) |
| ip_address | VARCHAR(15) |
| result | TINYINT(1) (1=success, 0=failed, indexed) |
| error_message | VARCHAR(1000) |

### Features

- **Email logging**: captures all `wp_mail()` calls with headers, attachments, IP, success/failure status.
- **View logs**: admin list table with search, date range filter (from-to), status filter (success/failed).
- **View email content**: AJAX modal with plain text and HTML preview tabs.
- **Resend email**: one-click resend from any log entry.
- **Export CSV**: export filtered logs as CSV file.
- **Auto-delete**: cron-based deletion of logs older than configurable N days.
- **Dashboard widget**: stats cards showing total, today, success, failed counts.
- **Settings**: allowed user roles, remove data on uninstall, dashboard widget toggle, DB size notification, auto-delete interval.

### Important filters/actions

- `el_wp_mail_log` -- modify mail info before logging.
- `el_email_log_before_insert` -- modify the log array just before DB insert.
- `el_email_log_inserted` -- fires after a log is inserted.
- `el_loaded` -- fires when plugin is fully loaded.

## Security notes

- All SQL queries use `$wpdb->prepare()`.
- AJAX endpoints use `check_ajax_referer()` for CSRF protection.
- NonceChecker dies with 403 on failed verification (not silent return).
- IP logging uses only `REMOTE_ADDR` (no X-Forwarded-For trust).
- Export and resend actions require `manage_email_logs` capability + nonce.

## Notes

- The autoloader is custom (not Composer): maps `EmailLog\` namespace to `include/` directory.
- `helper.php` is loaded as a plain file (not autoloaded), provides functions like `stringify()` and `is_advanced_search_term()`.
- Multisite is fully supported: table creation per blog, cleanup on blog deletion.
