-- Add featured_order column to products table
-- This allows admins to set a custom order for featured products
ALTER TABLE products
ADD COLUMN IF NOT EXISTS featured_order INT DEFAULT 0
AFTER is_featured;
-- Add index for better performance when sorting by featured order
ALTER TABLE products
ADD INDEX IF NOT EXISTS idx_featured_order (is_featured, featured_order);
-- Update existing featured products to have a default order based on their ID
UPDATE products
SET featured_order = id
WHERE is_featured = 1
    AND (
        featured_order IS NULL
        OR featured_order = 0
    );