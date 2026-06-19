
/**
 * b2b_discount_rule
 */
CREATE TABLE IF NOT EXISTS `PREFIX_b2b_discount_rule` (
        `id_b2b_discount_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_category` INT UNSIGNED NOT NULL,
        `id_manufacturer` INT UNSIGNED NOT NULL,
        `id_group` INT UNSIGNED NOT NULL,
        `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_b2b_discount_rule`),
    UNIQUE KEY `uniq_category_manufacturer_group` (
        `id_category`,
        `id_manufacturer`,
        `id_group`
    ),
    KEY `idx_category` (`id_category`),
    KEY `idx_manufacturer` (`id_manufacturer`),
    KEY `idx_group` (`id_group`),
    KEY `idx_active` (`active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/**
  * b2b_import
 */
CREATE TABLE IF NOT EXISTS `PREFIX_b2b_import` (
        `id_b2b_import` INT UNSIGNED NOT NULL AUTO_INCREMENT,

        `name` VARCHAR(255) NOT NULL,
        `source` VARCHAR(64) NOT NULL DEFAULT 'csv',

        `original_filename` VARCHAR(255) DEFAULT NULL,
        `stored_filename` VARCHAR(255) DEFAULT NULL,
        `file_path` VARCHAR(255) DEFAULT NULL,
        `file_size` BIGINT UNSIGNED DEFAULT NULL,
        `file_hash` CHAR(64) DEFAULT NULL,

        `status` VARCHAR(32) NOT NULL DEFAULT 'uploaded',

        `header_json` TEXT DEFAULT NULL,
        `file_offset` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `last_row_number` INT UNSIGNED NOT NULL DEFAULT 0,

        `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
        `parsed_rows` INT UNSIGNED NOT NULL DEFAULT 0,
        `validated_rows` INT UNSIGNED NOT NULL DEFAULT 0,
        `processed_rows` INT UNSIGNED NOT NULL DEFAULT 0,
        `success_rows` INT UNSIGNED NOT NULL DEFAULT 0,
        `warning_rows` INT UNSIGNED NOT NULL DEFAULT 0,
        `failed_rows` INT UNSIGNED NOT NULL DEFAULT 0,

        `created_by` INT UNSIGNED DEFAULT NULL,

        `started_at` DATETIME DEFAULT NULL,
        `finished_at` DATETIME DEFAULT NULL,
        `last_error` TEXT DEFAULT NULL,

        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,

    PRIMARY KEY (`id_b2b_import`),
    KEY `status` (`status`),
    KEY `source` (`source`),
    KEY `file_hash` (`file_hash`),
    KEY `date_add` (`date_add`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/**
* b2b_import_job`
 */
CREATE TABLE IF NOT EXISTS `PREFIX_b2b_import_job` (

    `id_b2b_import_job` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_b2b_import` INT UNSIGNED NOT NULL,

    `type` VARCHAR(64) NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',

    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,

    `started_at` DATETIME DEFAULT NULL,
    `finished_at` DATETIME DEFAULT NULL,
    `last_error` TEXT DEFAULT NULL,

    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,

    PRIMARY KEY (`id_b2b_import_job`),
    KEY `id_b2b_import` (`id_b2b_import`),
    KEY `import_type_status` (`id_b2b_import`, `type`, `status`),
    KEY `status_priority` (`status`, `priority`, `date_add`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/**
  * b2b_import_item
 */
CREATE TABLE IF NOT EXISTS `PREFIX_b2b_import_item` (

    `id_b2b_import_item` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_b2b_import` INT UNSIGNED NOT NULL,

    `row_number` INT UNSIGNED NOT NULL,
    `reference` VARCHAR(128) DEFAULT NULL,

    `payload_json` LONGTEXT NOT NULL,

    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `error_code` VARCHAR(64) DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,

    `processed_at` DATETIME DEFAULT NULL,

    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,

    PRIMARY KEY (`id_b2b_import_item`),
    KEY `id_b2b_import` (`id_b2b_import`),
    KEY `import_status` (`id_b2b_import`, `status`),
    KEY `reference` (`reference`),
    KEY `row_number` (`row_number`)

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/**
  * b2b_import_price_staging
 */
CREATE TABLE IF NOT EXISTS `PREFIX_b2b_import_price_staging` (

        `id_b2b_import_price_staging` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        `id_b2b_import` INT UNSIGNED NOT NULL,
        `id_b2b_import_item` BIGINT UNSIGNED NOT NULL,

        `reference` VARCHAR(128) NOT NULL,
        `id_product` INT UNSIGNED DEFAULT NULL,

        `source_price` DECIMAL(20,6) DEFAULT NULL,
        `currency_code` VARCHAR(8) DEFAULT NULL,
        `currency_rate` DECIMAL(20,6) DEFAULT NULL,
        `price_uah` DECIMAL(20,6) DEFAULT NULL,

        `active` TINYINT UNSIGNED DEFAULT NULL,

        `validation_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
        `processing_status` VARCHAR(32) NOT NULL DEFAULT 'pending',

        `error_code` VARCHAR(64) DEFAULT NULL,
        `error_message` TEXT DEFAULT NULL,

        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,

    PRIMARY KEY (`id_b2b_import_price_staging`),
    UNIQUE KEY `import_item` (`id_b2b_import_item`),
    KEY `id_b2b_import` (`id_b2b_import`),
    KEY `import_reference` (`id_b2b_import`, `reference`),
    KEY `id_product` (`id_product`),
    KEY `validation_status` (`validation_status`),
    KEY `processing_status` (`processing_status`)

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



/**
  * b2b_import_lock
 */
CREATE TABLE IF NOT EXISTS `PREFIX_b2b_import_lock` (

    `lock_name` VARCHAR(128) NOT NULL,
    `locked_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,

    PRIMARY KEY (`lock_name`),
    KEY `expires_at` (`expires_at`)

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





