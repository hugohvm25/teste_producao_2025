CREATE TABLE `whatsapp_messages` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`message_id` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`sender_phone` VARCHAR(30) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`receiver_phone` VARCHAR(30) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`message_text` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`sender_name` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`api_timestamp` BIGINT(20) NULL DEFAULT NULL,
	`received_at` TIMESTAMP NULL DEFAULT current_timestamp(),
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `message_id` (`message_id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=75
;
