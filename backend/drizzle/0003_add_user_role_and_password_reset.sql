ALTER TABLE `users` MODIFY `role` enum('admin','manager','user') NOT NULL DEFAULT 'user';--> statement-breakpoint
ALTER TABLE `users` ADD `reset_token_hash` varchar(255);--> statement-breakpoint
ALTER TABLE `users` ADD `reset_token_expires_at` timestamp;
