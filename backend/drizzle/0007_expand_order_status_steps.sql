ALTER TABLE orders
MODIFY COLUMN status ENUM(
  'pending',
  'confirmed',
  'processing',
  'packed',
  'shipped',
  'out_for_delivery',
  'delivered',
  'cancelled'
) NOT NULL DEFAULT 'pending';

ALTER TABLE order_status_history
MODIFY COLUMN status ENUM(
  'pending',
  'confirmed',
  'processing',
  'packed',
  'shipped',
  'out_for_delivery',
  'delivered',
  'cancelled'
) NOT NULL;
