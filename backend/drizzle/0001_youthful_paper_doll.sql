CREATE TABLE `tracking_pixels` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`type` enum('facebook','google_analytics','google_tag_manager','tiktok','custom') NOT NULL,
	`pixel_id` varchar(255),
	`script` text,
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT (now()),
	`updated_at` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `tracking_pixels_id` PRIMARY KEY(`id`)
);
