CREATE TABLE IF NOT EXISTS order_status_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  status ENUM('pending', 'confirmed', 'processing', 'delivered', 'cancelled') NOT NULL,
  note TEXT,
  created_by_user_id INT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_status_history_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_status_history_created_by_user_id FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
