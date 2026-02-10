# Module Comparison Report: Certificate Editor & Backend

## 1. Standardization Status
The following core features have been standardized across all four modules (`admin`, `scientific`, `poster26`, `participant`) to ensure consistency and reliability.

### Visual Consistency (Frontend)
- **A4 Landscape Scaling:** All modules now use the `resizeWorkspace()` function to scale the certificate preview to **1123px x 794px** (approx. A4 at 96 DPI), ensuring WYSIWYG accuracy.
- **Sidebar Control:** The `toggleSidebar()` function with auto-resizing (`setTimeout`) is implemented uniformly.
- **CSS Standards:** `overflow: hidden` is applied to the workspace to prevent scrollbars during scaling.

### Backend Reliability (ajax_handler.php)
- **Transactional Safety:** Certificate sending (`send_certificate`) is now wrapped in `mysqli_begin_transaction` / `commit` / `rollback` blocks.
- **Self-Healing Schema:**
  - `email_logs`: The `logEmailStatus` function automatically creates the table if it's missing.
  - `certificate_sent`: The update logic adds the column if it's missing (Error 1054 handling).
  - `certificate_templates`: The `save_template` action checks for table existence and creates it if needed.

## 2. Intentional Module Differences
Unique logic has been preserved in each module to support specific business requirements.

### Scientific Module (`scientific/admin/`)
- **Access Control:** Uses `require_once 'permission_helper.php'` and checks `has_permission($con, 'edit_users')` to restrict access.
- **Session Setup:** Uses `require_once 'session_setup.php'` instead of raw `session_start()`.

### Poster26 Module (`poster26/admin/`)
- **Bulk Automation:** Supports an `autogen` URL parameter to automatically generate and send certificates (used for bulk processing).
- **User Category:** Includes a specific `#user-category` element in the editor for display logic.

### Participant Module (`participant/admin/`)
- **Dynamic Templates:** Implements logic to switch between "Participant" and "Non-Participant" (Speaker/Exhibitor) layouts dynamically based on the `$isParticipant` flag.
- **Authentication:** Uses a manual `session_start()` and `isset($_SESSION['id'])` check in `ajax_handler.php`, whereas the main `admin` module uses `include 'includes/auth_helper.php'`.

## 3. Discrepancies & Recommendations
- **Auth Helper:** The `admin` module uses `includes/auth_helper.php`, while `participant` and `poster26` use manual session checks. This is currently functional but could be standardized in a future refactor.
- **Output Buffering:** `participant` and `scientific` backend handlers use `ob_start()` / `ob_clean()` aggressively to prevent JSON errors, which is a best practice that has been applied.

## 4. Files Verified
- `c:\xampp\htdocs\reg-sys.com\icpm2026\admin\certificate-editor.php` & `ajax_handler.php`
- `c:\xampp\htdocs\reg-sys.com\icpm2026\scientific\admin\certificate-editor.php` & `ajax_handler.php`
- `c:\xampp\htdocs\reg-sys.com\icpm2026\poster26\admin\certificate-editor.php` & `ajax_handler.php`
- `c:\xampp\htdocs\reg-sys.com\icpm2026\participant\admin\certificate-editor.php` & `ajax_handler.php`
