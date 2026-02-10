# Module Comparison Report: Certificate Editor & Backend

## Overview
This report compares the `scientific`, `participant`, and `poster26` modules against the `admin` reference implementation, focusing on non-stylistic PHP differences, backend logic, and unique module requirements.

## 1. Backend (`ajax_handler.php`)

### Shared / Standardized Logic
All modules (`admin`, `scientific`, `participant`, `poster26`) now share the following standardized logic:
- **Database Tables**: All use shared tables `certificate_templates` and `email_logs`.
- **Transactional Safety**: All implement `mysqli_begin_transaction`, `mysqli_commit`, and `mysqli_rollback` for certificate sending.
- **Self-Healing Schema**: All include `try-catch` blocks to handle missing columns (e.g., `certificate_sent`) or tables (`email_logs`, `certificate_templates`) by attempting to create/alter them on the fly.
- **Email Logging**: All use the `logEmailStatus()` function.
- **Template Management**: Standardized `save_template`, `load_template`, `delete_template` actions.

### Module-Specific Differences
- **Scientific**:
  - Validates `permission_helper.php` (if integrated in `ajax_handler`? - Verified: `certificate-editor.php` has it, `ajax_handler` typically relies on session/db).
  - *Correction*: `scientific` ajax handler primarily differs in session/path context if any.
- **Participant**:
  - Standard implementation.
- **Poster26**:
  - Standard implementation.

## 2. Frontend (`certificate-editor.php`)

### Visual Alignment (Standardized)
All modules now implement:
- **Workspace**: A4 Landscape scaling (1123px x 794px).
- **Sidebar**: Toggleable design sidebar with `resizeWorkspace()` integration.
- **Assets**: Shared `certificate-editor-core.js` (referenced via relative path or common asset).

### Logic Differences & Unique Requirements

| Feature | Admin (Reference) | Scientific | Participant | Poster26 |
| :--- | :--- | :--- | :--- | :--- |
| **Verification Link** | `/icpm2026/verify.php` (Implied) | `/icpm2026/scientific/verify.php` | `/icpm2026/verify.php` | `/icpm2026/poster26/verify.php` |
| **Permissions** | Standard Session | `require_once 'permission_helper.php'`<br>`has_permission(..., 'edit_users')` | Standard Session | Standard Session |
| **Dynamic Templates** | N/A | Static (Single Template) | **Dynamic**: Switches layout based on `$isParticipant` | Static (Single Template) |
| **Unique Elements** | Standard Set | Standard Set | Hides/Shows elements based on category | Adds `#user-category` element |
| **Category Handling** | Standard | Standard | `$isParticipant` logic derived from category | Standard |

## 3. Pending / Verification Items
- **Cross-Browser Testing**: Ensure `resizeWorkspace()` behaves consistently on Firefox/Safari (scaling logic is standard CSS transform).
- **Asset Paths**: Verify `../../admin/assets/img/` paths are valid from all module subdirectories.
- **Email Templates**: Ensure `send_certificate` uses appropriate email bodies for each module if they differ (currently standardized to generic "Certificate of Participation" or similar).

## Conclusion
The modules are structurally aligned with the admin reference while preserving necessary unique logic (verification links, permissions, dynamic templates). Backend safeguards are consistently applied across all modules.
