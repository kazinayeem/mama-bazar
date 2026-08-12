CREATE TABLE `banners` (
	`id` int AUTO_INCREMENT NOT NULL,
	`title` varchar(255),
	`subtitle` varchar(255),
	`image` varchar(500) NOT NULL,
	`image_mobile` varchar(500),
	`image_tablet` varchar(500),
	`link` varchar(500),
	`position` enum('hero','banner','promo','sidebar') NOT NULL DEFAULT 'hero',
	`button_text` varchar(100),
	`priority` int NOT NULL DEFAULT 0,
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `banners_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `bookings` (
	`id` int AUTO_INCREMENT NOT NULL,
	`customer_name` varchar(255) NOT NULL,
	`phone` varchar(20) NOT NULL,
	`email` varchar(255),
	`user_id` int,
	`booking_type` varchar(100) NOT NULL DEFAULT 'service',
	`service` varchar(255),
	`product_id` int,
	`start_date` datetime NOT NULL,
	`end_date` datetime NOT NULL,
	`quantity` int NOT NULL DEFAULT 1,
	`price` decimal(12,2) NOT NULL DEFAULT '0',
	`discount` decimal(12,2) NOT NULL DEFAULT '0',
	`additional_cost` decimal(12,2) NOT NULL DEFAULT '0',
	`total_amount` decimal(12,2) NOT NULL,
	`payment_status` enum('pending','partial','paid','refunded') NOT NULL DEFAULT 'pending',
	`status` enum('pending','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
	`notes` text,
	`attachment_url` varchar(500),
	`created_by_id` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `bookings_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `brands` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`logo` varchar(500),
	`banner_image` varchar(500),
	`description` text,
	`website` varchar(500),
	`country_of_origin` varchar(100),
	`featured` boolean NOT NULL DEFAULT false,
	`homepage_visibility` boolean NOT NULL DEFAULT true,
	`sort_order` int NOT NULL DEFAULT 0,
	`seo_title` varchar(255),
	`seo_description` text,
	`seo_keywords` varchar(500),
	`status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `brands_id` PRIMARY KEY(`id`),
	CONSTRAINT `brands_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `categories` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`parent_id` int,
	`image` varchar(500),
	`icon` varchar(500),
	`banner` varchar(500),
	`thumbnail` varchar(500),
	`description` text,
	`featured` boolean NOT NULL DEFAULT false,
	`sort_order` int NOT NULL DEFAULT 0,
	`homepage_visibility` boolean NOT NULL DEFAULT true,
	`seo_title` varchar(255),
	`seo_description` text,
	`seo_keywords` varchar(500),
	`status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `categories_id` PRIMARY KEY(`id`),
	CONSTRAINT `categories_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `checkout_notices` (
	`id` int AUTO_INCREMENT NOT NULL,
	`text` text NOT NULL,
	`priority` int NOT NULL DEFAULT 0,
	`background_color` varchar(50) DEFAULT '#FFF7ED',
	`text_color` varchar(50) DEFAULT '#9A3412',
	`icon` varchar(50) DEFAULT 'alert',
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `checkout_notices_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `collections` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`description` text,
	`image` varchar(500),
	`banner` varchar(500),
	`featured` boolean NOT NULL DEFAULT false,
	`homepage_visibility` boolean NOT NULL DEFAULT true,
	`sort_order` int NOT NULL DEFAULT 0,
	`start_date` datetime,
	`end_date` datetime,
	`status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `collections_id` PRIMARY KEY(`id`),
	CONSTRAINT `collections_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `colors` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(100) NOT NULL,
	`display_name` varchar(100),
	`hex` varchar(7) NOT NULL,
	`status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
	`sort_order` int NOT NULL DEFAULT 0,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `colors_id` PRIMARY KEY(`id`),
	CONSTRAINT `colors_name_unique` UNIQUE(`name`)
);
--> statement-breakpoint
CREATE TABLE `contact_messages` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(150) NOT NULL,
	`phone` varchar(20) NOT NULL,
	`email` varchar(255),
	`message` text NOT NULL,
	`status` enum('new','read','archived') NOT NULL DEFAULT 'new',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `contact_messages_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `costs` (
	`id` int AUTO_INCREMENT NOT NULL,
	`title` varchar(255) NOT NULL,
	`cost_type` varchar(100) NOT NULL DEFAULT 'operational',
	`quantity` decimal(12,2) NOT NULL DEFAULT '1',
	`unit_cost` decimal(12,2) NOT NULL DEFAULT '0',
	`total_cost` decimal(12,2) NOT NULL,
	`supplier_id` int,
	`product_id` int,
	`order_id` int,
	`booking_id` int,
	`cost_date` datetime NOT NULL,
	`payment_method` varchar(50) NOT NULL DEFAULT 'cash',
	`notes` text,
	`attachment_url` varchar(500),
	`created_by_id` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `costs_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `coupons` (
	`id` int AUTO_INCREMENT NOT NULL,
	`code` varchar(50) NOT NULL,
	`discount_type` enum('percentage','fixed') NOT NULL,
	`discount_value` decimal(10,2) NOT NULL,
	`min_order_amount` decimal(10,2) DEFAULT '0',
	`expiry_date` timestamp,
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `coupons_id` PRIMARY KEY(`id`),
	CONSTRAINT `coupons_code_unique` UNIQUE(`code`)
);
--> statement-breakpoint
CREATE TABLE `expense_categories` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`description` text,
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`sort_order` int NOT NULL DEFAULT 0,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `expense_categories_id` PRIMARY KEY(`id`),
	CONSTRAINT `expense_categories_name_unique` UNIQUE(`name`)
);
--> statement-breakpoint
CREATE TABLE `expenses` (
	`id` int AUTO_INCREMENT NOT NULL,
	`title` varchar(255) NOT NULL,
	`description` text,
	`category_id` int,
	`amount` decimal(12,2) NOT NULL,
	`payment_method` varchar(50) NOT NULL DEFAULT 'cash',
	`vendor` varchar(255),
	`member_id` int,
	`member_name` varchar(255),
	`expense_date` datetime NOT NULL,
	`reference_number` varchar(100),
	`attachment_url` varchar(500),
	`notes` text,
	`status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
	`created_by_id` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `expenses_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `marketing_integrations` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`type` enum('google_tag_manager','google_analytics','facebook_pixel','facebook_conversion_api','tiktok_pixel','custom_script') NOT NULL,
	`pixel_id` varchar(255),
	`script_code` text,
	`access_token` text,
	`test_event_code` varchar(100),
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `marketing_integrations_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `media_assets` (
	`id` int AUTO_INCREMENT NOT NULL,
	`url` varchar(1000) NOT NULL,
	`public_id` varchar(500),
	`filename` varchar(500) NOT NULL,
	`mime_type` varchar(100) NOT NULL,
	`size` int NOT NULL DEFAULT 0,
	`width` int,
	`height` int,
	`provider` enum('cloudinary','local') NOT NULL DEFAULT 'local',
	`folder` varchar(200) NOT NULL DEFAULT 'general',
	`alt` varchar(255),
	`uploader_id` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `media_assets_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `memos` (
	`id` int AUTO_INCREMENT NOT NULL,
	`title` varchar(255),
	`entity_type` varchar(30) NOT NULL,
	`entity_id` int,
	`url` varchar(1000) NOT NULL,
	`public_id` varchar(500),
	`filename` varchar(500) NOT NULL,
	`mime_type` varchar(100) NOT NULL,
	`size` int NOT NULL DEFAULT 0,
	`folder` varchar(200) NOT NULL DEFAULT 'memos',
	`notes` text,
	`uploaded_by_id` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `memos_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `newsletters` (
	`id` int AUTO_INCREMENT NOT NULL,
	`email` varchar(255) NOT NULL,
	`source` varchar(100) DEFAULT 'homepage',
	`status` enum('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
	`subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `newsletters_id` PRIMARY KEY(`id`),
	CONSTRAINT `newsletters_email_unique` UNIQUE(`email`)
);
--> statement-breakpoint
CREATE TABLE `order_items` (
	`id` int AUTO_INCREMENT NOT NULL,
	`order_id` int NOT NULL,
	`product_id` int NOT NULL,
	`variant_id` int,
	`size` varchar(30),
	`color` varchar(50),
	`quantity` int NOT NULL,
	`price` decimal(10,2) NOT NULL,
	CONSTRAINT `order_items_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `order_status_history` (
	`id` int AUTO_INCREMENT NOT NULL,
	`order_id` int NOT NULL,
	`status` enum('pending','payment_pending','payment_verification','confirmed','processing','packed','shipped','out_for_delivery','delivered','returned','cancelled','refunded') NOT NULL,
	`note` text,
	`created_by_user_id` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `order_status_history_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `orders` (
	`id` int AUTO_INCREMENT NOT NULL,
	`order_id` varchar(20) NOT NULL,
	`user_id` int,
	`customer_name` varchar(255) NOT NULL,
	`phone` varchar(20) NOT NULL,
	`alternative_phone` varchar(20),
	`email` varchar(255),
	`country` varchar(100),
	`division` varchar(100),
	`district` varchar(100),
	`upazila` varchar(100),
	`area` varchar(150),
	`address` text NOT NULL,
	`apartment` varchar(255),
	`postal_code` varchar(20),
	`shipping_method_id` int,
	`shipping_method_name` varchar(255),
	`shipping_cost` decimal(10,2) NOT NULL,
	`subtotal` decimal(10,2) DEFAULT '0',
	`coupon_code` varchar(50),
	`discount` decimal(10,2) DEFAULT '0',
	`tax` decimal(10,2) DEFAULT '0',
	`order_note` text,
	`checkout_notes` text,
	`admin_notes` text,
	`total_price` decimal(10,2) NOT NULL,
	`payment_method` enum('cod','bkash','nagad','rocket','bank','stripe','sslcommerz','paypal') NOT NULL DEFAULT 'cod',
	`transaction_id` varchar(100),
	`sender_number` varchar(30),
	`payment_screenshot` varchar(500),
	`payment_date` timestamp,
	`amount_sent` decimal(10,2),
	`payment_instructions` text,
	`courier_tracking_number` varchar(120),
	`payment_status` enum('pending','payment_pending','payment_verification','verified','success','failed','rejected','refunded') NOT NULL DEFAULT 'pending',
	`status` enum('pending','payment_pending','payment_verification','confirmed','processing','packed','shipped','out_for_delivery','delivered','returned','cancelled','refunded') NOT NULL DEFAULT 'pending',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `orders_id` PRIMARY KEY(`id`),
	CONSTRAINT `orders_order_id_unique` UNIQUE(`order_id`)
);
--> statement-breakpoint
CREATE TABLE `payment_methods` (
	`id` int AUTO_INCREMENT NOT NULL,
	`code` varchar(30) NOT NULL,
	`name` varchar(100) NOT NULL,
	`type` enum('cod','mobile_banking','bank','online') NOT NULL,
	`enabled` boolean NOT NULL DEFAULT true,
	`sort_order` int NOT NULL DEFAULT 0,
	`maintenance_mode` boolean NOT NULL DEFAULT false,
	`config` json,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `payment_methods_id` PRIMARY KEY(`id`),
	CONSTRAINT `payment_methods_code_unique` UNIQUE(`code`)
);
--> statement-breakpoint
CREATE TABLE `policy_pages` (
	`id` int AUTO_INCREMENT NOT NULL,
	`slug` varchar(150) NOT NULL,
	`title` varchar(200) NOT NULL,
	`content` text NOT NULL,
	`status` enum('published','draft') NOT NULL DEFAULT 'published',
	`last_updated` int NOT NULL DEFAULT 0,
	`updated_by` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `policy_pages_id` PRIMARY KEY(`id`),
	CONSTRAINT `policy_pages_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `product_relations` (
	`id` int AUTO_INCREMENT NOT NULL,
	`product_id` int NOT NULL,
	`related_product_id` int NOT NULL,
	`type` enum('frequently_bought_together','cross_sell','up_sell','accessories','similar') NOT NULL,
	CONSTRAINT `product_relations_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `product_specs` (
	`id` int AUTO_INCREMENT NOT NULL,
	`product_id` int NOT NULL,
	`label` varchar(255) NOT NULL,
	`value` text NOT NULL,
	`sort_order` int NOT NULL DEFAULT 0,
	CONSTRAINT `product_specs_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `product_variants` (
	`id` int AUTO_INCREMENT NOT NULL,
	`product_id` int NOT NULL,
	`name` varchar(500) NOT NULL,
	`options` json NOT NULL,
	`price` decimal(10,2),
	`discount_price` decimal(10,2),
	`sku` varchar(100),
	`barcode` varchar(100),
	`stock` int NOT NULL DEFAULT 0,
	`weight` varchar(50),
	`dimensions` varchar(100),
	`images` json,
	`thumbnail` varchar(500),
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`shipping_cost` decimal(10,2),
	`warranty` varchar(100),
	`availability` boolean NOT NULL DEFAULT true,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `product_variants_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `products` (
	`id` int AUTO_INCREMENT NOT NULL,
	`title` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`description` text,
	`short_description` text,
	`price` decimal(10,2) NOT NULL,
	`sale_price` decimal(10,2),
	`discount` decimal(10,2) DEFAULT '0',
	`cost_price` decimal(10,2) DEFAULT '0',
	`profit_margin` decimal(10,2) DEFAULT '0',
	`tax` decimal(10,2) DEFAULT '0',
	`vat` decimal(10,2) DEFAULT '0',
	`shipping_charge` decimal(10,2) DEFAULT '0',
	`cod_fee` decimal(10,2) DEFAULT '0',
	`flash_sale_price` decimal(10,2),
	`wholesale_price` decimal(10,2),
	`dealer_price` decimal(10,2),
	`category_id` int,
	`sub_category_id` int,
	`child_category_id` int,
	`collection_id` int,
	`brand_id` int,
	`brand` varchar(100),
	`vendor_id` int,
	`supplier_id` int,
	`supplier` varchar(255),
	`country_of_origin` varchar(100),
	`sku` varchar(100),
	`barcode` varchar(100),
	`tags` json,
	`warranty` varchar(100),
	`weight` varchar(50),
	`dimensions` varchar(100),
	`features` json,
	`return_policy` text,
	`warehouse` varchar(255),
	`video_url` varchar(500),
	`seo_title` varchar(255),
	`seo_description` text,
	`seo_keywords` varchar(500),
	`canonical_url` varchar(500),
	`og_image` varchar(500),
	`twitter_image` varchar(500),
	`structured_data` json,
	`draft` json,
	`emi_available` boolean NOT NULL DEFAULT false,
	`is_featured` boolean NOT NULL DEFAULT false,
	`is_trending` boolean NOT NULL DEFAULT false,
	`is_flash_sale` boolean NOT NULL DEFAULT false,
	`is_new_arrival` boolean NOT NULL DEFAULT false,
	`is_best_seller` boolean NOT NULL DEFAULT false,
	`is_limited_edition` boolean NOT NULL DEFAULT false,
	`is_official` boolean NOT NULL DEFAULT false,
	`is_hot_deal` boolean NOT NULL DEFAULT false,
	`is_archived` boolean NOT NULL DEFAULT false,
	`meta` json,
	`stock` int NOT NULL DEFAULT 0,
	`low_stock_alert` int NOT NULL DEFAULT 10,
	`min_order` int NOT NULL DEFAULT 1,
	`max_order` int,
	`unlimited_stock` boolean NOT NULL DEFAULT false,
	`backorder` boolean NOT NULL DEFAULT false,
	`track_inventory` boolean NOT NULL DEFAULT true,
	`stock_status` varchar(20) DEFAULT 'in_stock',
	`product_status` varchar(30) DEFAULT 'published',
	`images` json,
	`size_options` json,
	`color_options` json,
	`payment_methods` json,
	`payment_phone_number` varchar(20),
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `products_id` PRIMARY KEY(`id`),
	CONSTRAINT `products_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `rentals` (
	`id` int AUTO_INCREMENT NOT NULL,
	`rental_item` varchar(255) NOT NULL,
	`product_id` int,
	`customer_name` varchar(255) NOT NULL,
	`phone` varchar(20) NOT NULL,
	`email` varchar(255),
	`user_id` int,
	`quantity` int NOT NULL DEFAULT 1,
	`start_date` datetime NOT NULL,
	`end_date` datetime NOT NULL,
	`return_date` datetime,
	`rate_type` enum('daily','weekly','monthly') NOT NULL DEFAULT 'daily',
	`daily_rate` decimal(12,2) NOT NULL DEFAULT '0',
	`weekly_rate` decimal(12,2) NOT NULL DEFAULT '0',
	`monthly_rate` decimal(12,2) NOT NULL DEFAULT '0',
	`rate` decimal(12,2) NOT NULL DEFAULT '0',
	`duration_units` int NOT NULL DEFAULT 0,
	`security_deposit` decimal(12,2) NOT NULL DEFAULT '0',
	`discount` decimal(12,2) NOT NULL DEFAULT '0',
	`additional_charge` decimal(12,2) NOT NULL DEFAULT '0',
	`total_amount` decimal(12,2) NOT NULL,
	`payment_status` enum('pending','partial','paid','refunded') NOT NULL DEFAULT 'pending',
	`status` enum('reserved','rented','returned','overdue','cancelled') NOT NULL DEFAULT 'reserved',
	`notes` text,
	`attachment_url` varchar(500),
	`created_by_id` int,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `rentals_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `reviews` (
	`id` int AUTO_INCREMENT NOT NULL,
	`product_id` int NOT NULL,
	`user_id` int,
	`customer_name` varchar(255),
	`rating` int NOT NULL,
	`title` varchar(255),
	`comment` text NOT NULL,
	`status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `reviews_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `shipping_methods` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`charge` decimal(10,2) NOT NULL,
	`estimated_delivery` varchar(100),
	`description` text,
	`priority` int NOT NULL DEFAULT 0,
	`free_shipping_min_amount` decimal(10,2),
	`cod_available` boolean NOT NULL DEFAULT true,
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `shipping_methods_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `site_settings` (
	`id` int AUTO_INCREMENT NOT NULL,
	`key` varchar(100) NOT NULL,
	`value` text,
	CONSTRAINT `site_settings_id` PRIMARY KEY(`id`),
	CONSTRAINT `site_settings_key_unique` UNIQUE(`key`)
);
--> statement-breakpoint
CREATE TABLE `sizes` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(100) NOT NULL,
	`type` enum('clothing','shoes','general','custom') NOT NULL DEFAULT 'general',
	`status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
	`sort_order` int NOT NULL DEFAULT 0,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `sizes_id` PRIMARY KEY(`id`),
	CONSTRAINT `sizes_name_unique` UNIQUE(`name`)
);
--> statement-breakpoint
CREATE TABLE `suppliers` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`logo` varchar(500),
	`description` text,
	`contact` varchar(100),
	`phone` varchar(30),
	`email` varchar(255),
	`address` varchar(500),
	`notes` text,
	`status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `suppliers_id` PRIMARY KEY(`id`),
	CONSTRAINT `suppliers_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `tracking_logs` (
	`id` int AUTO_INCREMENT NOT NULL,
	`event_name` varchar(100) NOT NULL,
	`platform` varchar(50) NOT NULL,
	`payload` json,
	`status` enum('success','failed') NOT NULL DEFAULT 'success',
	`error_message` text,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `tracking_logs_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `user_addresses` (
	`id` int AUTO_INCREMENT NOT NULL,
	`user_id` int NOT NULL,
	`recipient_name` varchar(255) NOT NULL,
	`phone` varchar(20) NOT NULL,
	`alternative_phone` varchar(20),
	`email` varchar(255),
	`country` varchar(100),
	`division` varchar(100),
	`district` varchar(100),
	`upazila` varchar(100),
	`area` varchar(150),
	`shipping_area` varchar(100) NOT NULL,
	`address` text NOT NULL,
	`apartment` varchar(255),
	`postal_code` varchar(20),
	`is_default` boolean NOT NULL DEFAULT false,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `user_addresses_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `users` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`password` varchar(255) NOT NULL,
	`phone` varchar(20) NOT NULL,
	`shipping_area` varchar(100),
	`shipping_address` text,
	`role` enum('admin','manager','user') NOT NULL DEFAULT 'user',
	`status` enum('active','inactive') NOT NULL DEFAULT 'active',
	`reset_token_hash` varchar(255),
	`reset_token_expires_at` timestamp,
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `users_id` PRIMARY KEY(`id`),
	CONSTRAINT `users_phone_unique` UNIQUE(`phone`)
);
--> statement-breakpoint
CREATE TABLE `vendors` (
	`id` int AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`logo` varchar(500),
	`description` text,
	`contact` varchar(100),
	`phone` varchar(30),
	`email` varchar(255),
	`address` varchar(500),
	`notes` text,
	`status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
	`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `vendors_id` PRIMARY KEY(`id`),
	CONSTRAINT `vendors_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
ALTER TABLE `bookings` ADD CONSTRAINT `bookings_user_id_users_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `bookings` ADD CONSTRAINT `bookings_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `bookings` ADD CONSTRAINT `bookings_created_by_id_users_id_fk` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `categories` ADD CONSTRAINT `categories_parent_id_categories_id_fk` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `costs` ADD CONSTRAINT `costs_supplier_id_suppliers_id_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `costs` ADD CONSTRAINT `costs_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `costs` ADD CONSTRAINT `costs_order_id_orders_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `costs` ADD CONSTRAINT `costs_booking_id_bookings_id_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `costs` ADD CONSTRAINT `costs_created_by_id_users_id_fk` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `expenses` ADD CONSTRAINT `expenses_category_id_expense_categories_id_fk` FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `expenses` ADD CONSTRAINT `expenses_member_id_users_id_fk` FOREIGN KEY (`member_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `expenses` ADD CONSTRAINT `expenses_created_by_id_users_id_fk` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `media_assets` ADD CONSTRAINT `media_assets_uploader_id_users_id_fk` FOREIGN KEY (`uploader_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `memos` ADD CONSTRAINT `memos_uploaded_by_id_users_id_fk` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_order_id_orders_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_variant_id_product_variants_id_fk` FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `order_status_history` ADD CONSTRAINT `order_status_history_order_id_orders_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `order_status_history` ADD CONSTRAINT `order_status_history_created_by_user_id_users_id_fk` FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_user_id_users_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `product_relations` ADD CONSTRAINT `product_relations_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `product_relations` ADD CONSTRAINT `product_relations_related_product_id_products_id_fk` FOREIGN KEY (`related_product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `product_specs` ADD CONSTRAINT `product_specs_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `product_variants` ADD CONSTRAINT `product_variants_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_category_id_categories_id_fk` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_collection_id_collections_id_fk` FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_vendor_id_vendors_id_fk` FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_supplier_id_suppliers_id_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `rentals` ADD CONSTRAINT `rentals_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `rentals` ADD CONSTRAINT `rentals_user_id_users_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `rentals` ADD CONSTRAINT `rentals_created_by_id_users_id_fk` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `reviews` ADD CONSTRAINT `reviews_product_id_products_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `reviews` ADD CONSTRAINT `reviews_user_id_users_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE `user_addresses` ADD CONSTRAINT `user_addresses_user_id_users_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
CREATE INDEX `expenses_expense_date_idx` ON `expenses` (`expense_date`);--> statement-breakpoint
CREATE INDEX `expenses_member_id_idx` ON `expenses` (`member_id`);--> statement-breakpoint
CREATE INDEX `expenses_category_id_idx` ON `expenses` (`category_id`);--> statement-breakpoint
CREATE INDEX `expenses_status_idx` ON `expenses` (`status`);