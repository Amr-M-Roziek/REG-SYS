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

## Migration
The migration is performed by the `update_db_schema.php` script (which was run and then deleted/skipped).
The script checks for the existence of each column before adding it to ensure idempotency.

## Rollback
To rollback these changes, use the `rollback_db_schema.php` script.
This script will DROP the added columns.
**WARNING:** Running the rollback script will permanently delete all data stored in these columns.

## Backward Compatibility
The new columns are nullable or have default values, ensuring that existing application logic that doesn't use these fields will continue to function correctly.
