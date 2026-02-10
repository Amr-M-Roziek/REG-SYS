# Poster Admin — Instant Search

## Overview
- Adds a search box above the users table for instant filtering.
- Filters across all `regsys_poster26.users` columns using efficient server-side queries.
- Uses a 300ms debounce for real-time responsiveness without overloading the server.
- Matches are case-sensitive and highlighted in results.
- Includes a clear button and a “No results found” message.
- Preserves existing table behavior and is fully responsive and accessible.

## How To Use
- Type in the search box to filter the table. Filtering runs automatically after you pause typing.
- Press Escape to clear the search quickly, or click the “Clear” button.
- Matching text in the results is highlighted.
- When no rows match, the table displays “No results found”.

## Accessibility
- The search container uses `role="search"` with descriptive labels.
- Live region updates announce the current result count: screen readers can read changes.
- Keyboard support: Escape clears the search; tabbing moves between input and clear button.

## Behavior Details
- Case-sensitive matching: “Alice” does not match “alice”.
- Search scope: all columns, including co-authors, email, organization, category, contact, company ref, and timestamps.
- Highlighting uses `<mark>` for matched fragments.

## Performance
- Queries are server-side with `LIKE ... COLLATE utf8mb4_bin` across indexed text columns.
- Returns up to 500 rows per request; adjust in `manage-users.php` if needed.
- For very large datasets, consider adding composite indexes or fulltext indexes for frequent fields.

## Troubleshooting
- If the search returns no results unexpectedly, verify the case of your query.
- Ensure the database connection is healthy; the search relies on `dbconnection.php`.
- If highlight appears broken, check for unusual Unicode characters in input.

## Database Configuration
- The application now uses the MySQL database `regsys_poster26` instead of `regsys_poster`.
- Update occurs in:
  - `poster26/dbconnection.php` and `poster26/admin/dbconnection.php` via `DB_NAME='regsys_poster26'`.
- No `.env` or ORM configs are present in this project; connections are configured in the PHP files above.
- Deployment checklist:
  - Ensure `regsys_poster26` exists and has the same schema and data as `regsys_poster`.
  - Grant privileges to the MySQL user configured by `DB_USER` to `regsys_poster26`.
  - Restart PHP-FPM/Apache if opcache is enabled to pick up changes.
  - Test by loading the Register page and performing a sample signup (using a test email).

## Co-Author Emails Schema
- The `users` table stores author and co-author data. Email columns for co-authors must exist:
  - `coauth1email`, `coauth2email`, `coauth3email`, `coauth4email`, `coauth5email` as `VARCHAR(255) NULL`.
- Add columns if missing (run for each target database):
  ```
  ALTER TABLE `users`
    ADD COLUMN `coauth1email` VARCHAR(255) NULL AFTER `coauth1nationality`,
    ADD COLUMN `coauth2email` VARCHAR(255) NULL AFTER `coauth2nationality`,
    ADD COLUMN `coauth3email` VARCHAR(255) NULL AFTER `coauth3nationality`,
    ADD COLUMN `coauth4email` VARCHAR(255) NULL AFTER `coauth4nationality`,
    ADD COLUMN `coauth5email` VARCHAR(255) NULL AFTER `coauth5nationality`;
  ```
- Optional indexes (improve lookups):
  ```
  ALTER TABLE `users`
    ADD INDEX (`coauth1email`), ADD INDEX (`coauth2email`), ADD INDEX (`coauth3email`),
    ADD INDEX (`coauth4email`), ADD INDEX (`coauth5email`);
  ```
- Validation:
  - Front-end and server-side validation require co-author emails whenever a co-author is provided.
  - Admin edit form validates and saves co-author emails.
- Optional DB-level guards (MySQL trigger minimal checks):
  ```
  DELIMITER $$
  CREATE TRIGGER `users_email_guard_ins` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.coauth1name IS NOT NULL AND (NEW.coauth1email IS NULL OR NEW.coauth1email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 1 email required';
    END IF;
    IF NEW.coauth2name IS NOT NULL AND (NEW.coauth2email IS NULL OR NEW.coauth2email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 2 email required';
    END IF;
    IF NEW.coauth3name IS NOT NULL AND (NEW.coauth3email IS NULL OR NEW.coauth3email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 3 email required';
    END IF;
    IF NEW.coauth4name IS NOT NULL AND (NEW.coauth4email IS NULL OR NEW.coauth4email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 4 email required';
    END IF;
    IF NEW.coauth5name IS NOT NULL AND (NEW.coauth5email IS NULL OR NEW.coauth5email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 5 email required';
    END IF;
  END$$
  CREATE TRIGGER `users_email_guard_upd` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.coauth1name IS NOT NULL AND (NEW.coauth1email IS NULL OR NEW.coauth1email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 1 email required';
    END IF;
    IF NEW.coauth2name IS NOT NULL AND (NEW.coauth2email IS NULL OR NEW.coauth2email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 2 email required';
    END IF;
    IF NEW.coauth3name IS NOT NULL AND (NEW.coauth3email IS NULL OR NEW.coauth3email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 3 email required';
    END IF;
    IF NEW.coauth4name IS NOT NULL AND (NEW.coauth4email IS NULL OR NEW.coauth4email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 4 email required';
    END IF;
    IF NEW.coauth5name IS NOT NULL AND (NEW.coauth5email IS NULL OR NEW.coauth5email NOT LIKE '%@%') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Co-Author 5 email required';
    END IF;
  END$$
  DELIMITER ;
  ```
- Data migration:
  - If historical co-author emails are unavailable, you may insert placeholders to avoid nulls:
    ```
    UPDATE users
    SET coauth1email = CONCAT('placeholder+', id, '+CO1@icpm.local')
    WHERE coauth1name IS NOT NULL AND (coauth1email IS NULL OR coauth1email='');
    UPDATE users
    SET coauth2email = CONCAT('placeholder+', id, '+CO2@icpm.local')
    WHERE coauth2name IS NOT NULL AND (coauth2email IS NULL OR coauth2email='');
    UPDATE users
    SET coauth3email = CONCAT('placeholder+', id, '+CO3@icpm.local')
    WHERE coauth3name IS NOT NULL AND (coauth3email IS NULL OR coauth3email='');
    UPDATE users
    SET coauth4email = CONCAT('placeholder+', id, '+CO4@icpm.local')
    WHERE coauth4name IS NOT NULL AND (coauth4email IS NULL OR coauth4email='');
    UPDATE users
    SET coauth5email = CONCAT('placeholder+', id, '+CO5@icpm.local')
    WHERE coauth5name IS NOT NULL AND (coauth5email IS NULL OR coauth5email='');
    ```
  - Prefer updating real addresses when available from source records.
## Testing
- Unit tests are located in `icpm2026/poster/admin/tests/search.test.php`.
- Run: `php icpm2026/poster/admin/tests/search.test.php`
- Tests cover highlight insertion and case-sensitive behavior.
