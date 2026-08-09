-- Add payment methods to products
ALTER TABLE products 
ADD COLUMN payment_methods JSON DEFAULT '["cod"]' AFTER images,
ADD COLUMN payment_phone_number VARCHAR(20) AFTER payment_methods;

-- Add payment tracking to orders
ALTER TABLE orders 
ADD COLUMN transaction_id VARCHAR(100) AFTER payment_method,
ADD COLUMN payment_status ENUM('pending', 'verified', 'success') DEFAULT 'pending' AFTER transaction_id;
