-- Migration: add category and description to issues table
-- Run this on your local MySQL instance (e.g., via phpMyAdmin or mysql CLI)

ALTER TABLE `issues`
  ADD COLUMN `category` VARCHAR(50) NULL AFTER `user_id`,
  ADD COLUMN `description` TEXT NULL AFTER `category`;

-- Optional: populate 'category' from existing patterns if available
-- UPDATE issues SET category = 'unspecified' WHERE category IS NULL;

-- Note: After running this migration, submissions from the web UI will persist category and description.