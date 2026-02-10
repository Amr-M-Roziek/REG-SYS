# Registration Workflow (Fixed)

## Overview
- Validates required fields and abstract upload.
- Inserts user into `users` via prepared statements with adaptive schema.
- Verifies insert by selecting the row back and initializes session.
- Issues HTTP 302 redirect to `welcome.php` and logs events; falls back to meta-refresh if headers already sent.

## Steps
- Input validation:
  - Ensures abstract upload present and valid type/size.
  - Validates co-author emails only when provided; duplicates allowed.
  - Main author email must be unique.
- Adaptive schema:
  - Ensures optional columns exist (`coauthXemail`, `postertitle`, `abstract_*`) before insert.
- Insert + verify:
  - Uses prepared `INSERT` with parameter counts matching schema.
  - After `INSERT`, selects user by email to confirm and populates `$_SESSION`.
- Redirect:
  - Computes scheme-aware `welcome.php` URL.
  - Sends `302 Location` and logs `REDIRECT_302_SENT`.
  - If headers already sent, shows overlay page and meta refresh; logs `REDIRECT_FALLBACK`.

## Error Handling
- Logs all key events to `submissions.log` via `log_submission`.
- On DB prepare/execute failure: logs and shows alert.
- On post-insert verify failure: logs and alerts the user.
- On missing/invalid abstract: alerts and halts.

## Tests
- `tests/register-success-http.php`: multipart registration, expects `302` and DB row exists.
- `tests/register-fail-missing-abstract.php`: no file, expects validation alert.
- `tests/register-db-verify.php`: verifies DB fields for a given email.

## Notes
- Local session cookies configured for HTTP (`cookie_secure=0`, `SameSite=Lax`, `path=/`).
- Production over HTTPS should set `cookie_secure=1` and maintain scheme-aware redirects.
