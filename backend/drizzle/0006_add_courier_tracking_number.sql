ALTER TABLE orders
ADD COLUMN IF NOT EXISTS courier_tracking_number VARCHAR(120);
