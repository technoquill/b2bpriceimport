<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_0($module): bool
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
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'b2b_import_product_mapping` (
            `id_b2b_import_product_mapping` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `reference` VARCHAR(128) NOT NULL,
            `id_product` INT UNSIGNED NOT NULL,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_b2b_import_product_mapping`),
            UNIQUE KEY `reference` (`reference`),
            KEY `id_product` (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_
        . ' DEFAULT CHARSET=' . $charset
        . ' COLLATE=' . $collation
    );
}
