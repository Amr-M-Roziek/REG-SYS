# Database Schema Changes

## Overview
This document outlines the changes made to the `regsys_poster26` database to support the new Supervisor feature.

## Changes
Added the following columns to the `users` table:

| Column Name            | Data Type     | Default | Description                                      |
| ---------------------- | ------------- | ------- | ------------------------------------------------ |
| `supervisor_choice`    | VARCHAR(10)   | 'no'    | Indicates if a supervisor is added ('yes'/'no')  |
| `supervisor_name`      | VARCHAR(255)  | NULL    | Full name of the supervisor                      |
| `supervisor_nationality`| VARCHAR(255) | NULL    | Nationality of the supervisor                    |
| `supervisor_contact`   | VARCHAR(20)   | NULL    | Contact number of the supervisor                 |
| `supervisor_email`     | VARCHAR(255)  | NULL    | Email address of the supervisor                  |

## Mobile Number Integration (2025-12-25)
Added the following columns to the `users` table for team member mobile numbers:

| Column Name            | Data Type     | Default | Description                                      |
| ---------------------- | ------------- | ------- | ------------------------------------------------ |
| `coauth1mobile`        | VARCHAR(15)   | NULL    | Mobile number of 1st team member                 |
| `coauth2mobile`        | VARCHAR(15)   | NULL    | Mobile number of 2nd team member                 |
| `coauth3mobile`        | VARCHAR(15)   | NULL    | Mobile number of 3rd team member                 |
| `coauth4mobile`        | VARCHAR(15)   | NULL    | Mobile number of 4th team member                 |
| `coauth5mobile`        | VARCHAR(15)   | NULL    | Mobile number of 5th team member                 |

## Migration
The migration is performed by `migrate_mobile.php` (for mobile columns) and dynamic checks in `index.php`.
The migration is performed by the `update_db_schema.php` script (which was run and then deleted/skipped).
The script checks for the existence of each column before adding it to ensure idempotency.

## Rollback
To rollback these changes, use the `rollback_db_schema.php` script.
This script will DROP the added columns.
**WARNING:** Running the rollback script will permanently delete all data stored in these columns.

## Backward Compatibility
The new columns are nullable or have default values, ensuring that existing application logic that doesn't use these fields will continue to function correctly.
