-- Quick fix: Add image_path columns manually
-- Run this SQL script in your database if migrations can't be run

-- Add image_path to vehicles table
ALTER TABLE vehicles ADD COLUMN image_path VARCHAR(255) NULL AFTER notes;

-- Add image_path to accommodations table  
ALTER TABLE accommodations ADD COLUMN image_path VARCHAR(255) NULL AFTER description;
