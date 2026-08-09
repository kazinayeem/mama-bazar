CREATE TABLE `marketing_integrations` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`type` enum('google_tag_manager','google_analytics','facebook_pixel','facebook_conversion_api','tiktok_pixel','custom_script') NOT NULL,
	`pixel_id` varchar(255),
	`script_code` text,
	`access_token` text,
	`test_event_code` varchar(100),
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT (now()),
	`updated_at` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `marketing_integrations_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `tracking_logs` (
	`id` int AUTO_INCREMENT NOT NULL,
	`event_name` varchar(100) NOT NULL,
	`platform` varchar(50) NOT NULL,
	`payload` json,
	`status` enum('success','failed') NOT NULL DEFAULT 'success',
	`error_message` text,
	`created_at` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `tracking_logs_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
DROP TABLE `tracking_pixels`;--> statement-breakpoint
ALTER TABLE `orders` ADD `order_id` varchar(20);--> statement-breakpoint
UPDATE `orders` SET `order_id` = CONCAT('GHB-', UPPER(SUBSTRING(MD5(RAND()), 1, 6))) WHERE `order_id` IS NULL;--> statement-breakpoint
ALTER TABLE `orders` MODIFY `order_id` varchar(20) NOT NULL;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_order_id_unique` UNIQUE(`order_id`);