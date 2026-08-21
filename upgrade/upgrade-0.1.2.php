<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_1_2($module): bool
{
    $encoding = Db::getInstance()->getRow(
        'SELECT
            DEFAULT_CHARACTER_SET_NAME AS charset,
            DEFAULT_COLLATION_NAME AS collation
         FROM information_schema.SCHEMATA
         WHERE SCHEMA_NAME = DATABASE()'
    );
    $charset = is_array($encoding) ? (string) ($encoding['charset'] ?? '') : '';
    $collation = is_array($encoding) ? (string) ($encoding['collation'] ?? '') : '';
    $validIdentifier = '/^[a-zA-Z0-9_]+$/D';

    if (
        preg_match($validIdentifier, $charset) !== 1
        || preg_match($validIdentifier, $collation) !== 1
    ) {
        return false;
    }

    return (bool) Db::getInstance()->execute(
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'b2b_audit_log` (
            `id_b2b_audit_log` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `action` VARCHAR(96) NOT NULL,
            `entity_type` VARCHAR(32) NOT NULL,
            `entity_id` VARCHAR(255) DEFAULT NULL,
            `result` VARCHAR(16) NOT NULL,
            `actor_type` VARCHAR(32) NOT NULL DEFAULT "system",
            `actor_id` INT UNSIGNED DEFAULT NULL,
            `actor_name` VARCHAR(255) DEFAULT NULL,
            `channel` VARCHAR(32) NOT NULL DEFAULT "system",
            `message` TEXT NOT NULL,
            `before_json` LONGTEXT DEFAULT NULL,
            `after_json` LONGTEXT DEFAULT NULL,
            `context_json` LONGTEXT DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_b2b_audit_log`),
            KEY `date_add` (`date_add`),
            KEY `entity` (`entity_type`, `entity_id`),
            KEY `action_result` (`action`, `result`),
            KEY `channel` (`channel`),
            KEY `actor_id` (`actor_id`)
        ) ENGINE=' . _MYSQL_ENGINE_
        . ' DEFAULT CHARSET=' . $charset
        . ' COLLATE=' . $collation
    );
}
