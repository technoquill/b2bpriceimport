<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_1_1($module): bool
{
    $column = Db::getInstance()->getRow(
        'SHOW COLUMNS FROM `' . _DB_PREFIX_ . "b2b_import_price_staging` LIKE 'product_name'"
    );

    if (is_array($column)) {
        return true;
    }

    return (bool) Db::getInstance()->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'b2b_import_price_staging`'
        . ' ADD `product_name` VARCHAR(255) DEFAULT NULL AFTER `reference`'
    );
}
