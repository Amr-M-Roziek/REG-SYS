# Co-Author Email Field — Poster 26

## Overview
- Co-authors now require an email address during signup.
- Email is validated client-side and server-side.
- Emails are stored in the `users` table as `coauth1email` … `coauth5email`.

## UI
- Each co-author section includes:
  - Full Name (required)
  - Nationality (required)
  - Email (required; format checked)
  - Mobile Number (optional; with country code)
- Visibility controlled by “Number of Co Others” dropdown; only visible fields are required.

## Validation
- Client-side regex checks email format before submit.
- Server-side checks:
  - Email format via `filter_var`.
  - No duplicate co-author emails within the same submission.
  - Must differ from the main author’s email.
- Mobile numbers are stored with country codes.

## Database
- New columns added if missing:
  - `coauth1email`, `coauth2email`, `coauth3email`, `coauth4email`, `coauth5email` (VARCHAR(255), nullable).
  - `coauth1mobile`, `coauth2mobile`, `coauth3mobile`, `coauth4mobile`, `coauth5mobile` (VARCHAR(15), nullable).
- Existing records with co-author names but empty emails are populated with placeholders (`placeholder+<id>+COx@icpm.local`).

## API/Backend
- The registration endpoint handles email fields in POST and inserts them into the database.
- On validation failure, the signup halts with an error message.

## Notes
- Ensure backups are taken before schema changes in production.
- Placeholder emails are for data integrity; they should not be used for mailing.

