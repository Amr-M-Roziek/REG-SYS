# Technical Specifications - User Management Table Enhancements

## Overview
This document outlines the technical implementation of enhancements made to the User Management table (`manage-users.php`) in the Admin panel. The changes improve data visibility, user persistence, and interface responsiveness.

## New Features

### 1. Enhanced Reference Number Display
*   **Problem:** Long Reference Numbers were truncated, making them unreadable.
*   **Solution:** CSS overrides applied to `th:nth-child(2)` and `td:nth-child(2)` to force full display.
*   **Technical Detail:**
    *   `white-space: nowrap !important`
    *   `overflow: visible !important`
    *   `min-width: 150px`

### 2. Column Visibility Persistence
*   **Functionality:** Users can toggle the visibility of "Co-author" and "Email" columns. These preferences persist across page reloads and browser sessions.
*   **Implementation:**
    *   **Storage:** Browser Cookies (7-day expiration).
    *   **Keys:** `col_vis_hide-coauth1`, `col_vis_hide-emails`, etc.
    *   **Logic:** JavaScript checks cookies on page load and applies `show()`/`hide()` to table cells (`td`, `th`) based on column index.
    *   **Security:** Cookies set with `SameSite=Lax` and `Path=/`.

### 3. Search Query Persistence
*   **Functionality:** The search box retains its value and filters results automatically after a page reload or navigation.
*   **Implementation:**
    *   **Storage:** Browser Cookie `search_query` (7-day expiration).
    *   **Logic:** On page load, if cookie exists, populates input and triggers `fetchRows()`.

### 4. Table Controls
*   **Refresh Table:**
    *   Triggers an AJAX reload of the table body via `fetchRows()`.
    *   Displays a loading indicator (`#loading-indicator`) during the operation.
    *   Fallbacks to `location.reload()` if AJAX fails or is unavailable.
*   **Reset Settings:**
    *   Clears all `col_vis_*` cookies.
    *   Resets checkboxes to default (unchecked/hidden).
    *   Re-applies visibility rules immediately.

## Architecture

### Frontend (JavaScript/jQuery)
*   **Event Delegation:** Used for dynamic content (table rows).
*   **State Management:** `document.cookie` used as the source of truth for UI state.
*   **AJAX:** `window.fetchRows` exposed globally to allow the Refresh button to trigger the search/filter logic defined in the IIFE.

### Backend (PHP)
*   **AJAX Handler:** `manage-users.php` handles `?ajax=1` requests to return only the `<tbody>` content.
*   **Data Export:** Existing `mysqldump` logic preserved.

## Testing & Verification
*   **Unit Tests:**
    *   `tests/test_ref_display.php`: Verifies CSS rules for Reference Number.
    *   `tests/test_column_indices.php`: Verifies column hiding logic.
*   **Manual Verification:**
    *   Reload page -> Search term and Column choices remain.
    *   Click Refresh -> Loading spinner appears, data updates.
    *   Click Reset -> All columns hide, cookies cleared.

## Future Considerations
*   **Column Ordering:** Currently not implemented. Requires a library like DataTables or complex DOM manipulation.
*   **Server-Side Filtering:** Initial page load currently fetches default 500 rows. Search persistence triggers a second AJAX call. Optimization would be to read the cookie in PHP and filter the initial query.
