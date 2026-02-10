-- Database Update Script for Mobile Number Integration
-- Date: 2025-12-25

-- 1. Add mobile number columns for team members
ALTER TABLE `users` ADD COLUMN `coauth1mobile` VARCHAR(15) NULL;
ALTER TABLE `users` ADD COLUMN `coauth2mobile` VARCHAR(15) NULL;
ALTER TABLE `users` ADD COLUMN `coauth3mobile` VARCHAR(15) NULL;
ALTER TABLE `users` ADD COLUMN `coauth4mobile` VARCHAR(15) NULL;
ALTER TABLE `users` ADD COLUMN `coauth5mobile` VARCHAR(15) NULL;

-- 2. (Optional) Ensure other recent columns exist if not already present
-- Uncomment these if you haven't added them yet

-- Supervisor fields
-- ALTER TABLE `users` ADD COLUMN `supervisor_choice` VARCHAR(3) NULL DEFAULT 'no';
-- ALTER TABLE `users` ADD COLUMN `supervisor_name` VARCHAR(255) NULL;
-- ALTER TABLE `users` ADD COLUMN `supervisor_nationality` VARCHAR(255) NULL;
-- ALTER TABLE `users` ADD COLUMN `supervisor_contact` VARCHAR(20) NULL;
-- ALTER TABLE `users` ADD COLUMN `supervisor_email` VARCHAR(255) NULL;

-- Co-author emails
-- ALTER TABLE `users` ADD COLUMN `coauth1email` VARCHAR(255) NULL;
-- ALTER TABLE `users` ADD COLUMN `coauth2email` VARCHAR(255) NULL;
-- ALTER TABLE `users` ADD COLUMN `coauth3email` VARCHAR(255) NULL;
-- ALTER TABLE `users` ADD COLUMN `coauth4email` VARCHAR(255) NULL;
-- ALTER TABLE `users` ADD COLUMN `coauth5email` VARCHAR(255) NULL;
