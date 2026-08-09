-- Newsletters table (matches src/config/schema.ts)
CREATE TABLE IF NOT EXISTS `newsletters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `source` varchar(100) DEFAULT 'homepage',
  `status` enum('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletters_email_unique` (`email`)
);
