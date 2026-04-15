<?php

namespace App\Core\Schema;

class RequiredTables
{
    public static function definitions(): array
    {
        return [
            new TableDefinition(
                'users',
                "CREATE TABLE `users` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `username` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(191) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `user_role` ENUM('author', 'admin') NOT NULL DEFAULT 'author',
                    `status` ENUM('pending_approval', 'approved', 'blocked') NOT NULL DEFAULT 'pending_approval',
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_username_unique` (`username`),
                    UNIQUE KEY `users_email_unique` (`email`),
                    KEY `users_status_index` (`status`),
                    KEY `users_role_index` (`user_role`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ),
            new TableDefinition(
                'refreshtokens',
                "CREATE TABLE `refreshtokens` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `refreshtoken` CHAR(128) NOT NULL,
                    `userid` BIGINT UNSIGNED NOT NULL,
                    `issued_at` INT UNSIGNED NOT NULL,
                    `expires_at` INT UNSIGNED NOT NULL,
                    `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `refreshtokens_token_unique` (`refreshtoken`),
                    KEY `refreshtokens_userid_index` (`userid`),
                    KEY `refreshtokens_expires_at_index` (`expires_at`),
                    CONSTRAINT `refreshtokens_userid_fk`
                        FOREIGN KEY (`userid`) REFERENCES `users` (`id`)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ),
            new TableDefinition(
                'uploads',
                "CREATE TABLE `uploads` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `uploaded_to` VARCHAR(100) NULL DEFAULT NULL,
                    `file_name` VARCHAR(255) NOT NULL,
                    `base_path` VARCHAR(255) NOT NULL,
                    `mime_type` VARCHAR(100) NOT NULL,
                    `file_size` BIGINT UNSIGNED NOT NULL,
                    `alt_text` VARCHAR(200) NULL DEFAULT NULL,
                    `captions` VARCHAR(200) NULL DEFAULT NULL,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `uploads_user_id_index` (`user_id`),
                    CONSTRAINT `uploads_user_id_fk`
                        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ),
            new TableDefinition(
                'posts',
                "CREATE TABLE `posts` (
                    `post_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `post_title` VARCHAR(200) NOT NULL,
                    `post_slug` VARCHAR(255) NOT NULL,
                    `post_content` MEDIUMTEXT NOT NULL,
                    `post_excerpt` VARCHAR(300) NULL DEFAULT NULL,
                    `post_featured_image` BIGINT UNSIGNED NULL DEFAULT NULL,
                    `author_id` BIGINT UNSIGNED NOT NULL,
                    `post_status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`post_id`),
                    UNIQUE KEY `posts_slug_unique` (`post_slug`),
                    KEY `posts_author_id_index` (`author_id`),
                    KEY `posts_status_index` (`post_status`),
                    KEY `posts_featured_image_index` (`post_featured_image`),
                    CONSTRAINT `posts_author_id_fk`
                        FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
                        ON DELETE CASCADE,
                    CONSTRAINT `posts_featured_image_fk`
                        FOREIGN KEY (`post_featured_image`) REFERENCES `uploads` (`id`)
                        ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ),
        ];
    }
}
