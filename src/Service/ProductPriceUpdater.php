<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use Db;
use DbQuery;
use Product;
use RuntimeException;
use Shop;
use SpecificPrice;

final class ProductPriceUpdater
{
    public function updateProduct(int $idProduct, float $priceUah, ?int $active): void
    {
        if ($idProduct <= 0) {
            throw new RuntimeException('Invalid product id.');
        }

        if ($priceUah < 0) {
            throw new RuntimeException('Price cannot be negative.');
        }

        $data = [
            'price' => $priceUah,
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if ($active !== null) {
            $data['active'] = $active;
        }

        Db::getInstance()->update('product', $data, 'id_product = ' . (int) $idProduct);
        Db::getInstance()->update('product_shop', $data, 'id_product = ' . (int) $idProduct);
    }

    public function applyDiscountMatrix(int $idProduct): void
    {
        $product = new Product($idProduct);
        $idManufacturer = (int) $product->id_manufacturer;

        if ($idManufacturer <= 0) {
            return;
        }

        $categoryIds = Product::getProductCategories($idProduct);
        if (!is_array($categoryIds) || empty($categoryIds)) {
            return;
        }

        $categoryIds = array_map('intval', $categoryIds);

        $query = new DbQuery();
        $query->select('id_group, discount_percent');
        $query->from('b2b_discount_rule');
        $query->where('active = 1');
        $query->where('id_manufacturer = ' . (int) $idManufacturer);
        $query->where('id_category IN (' . implode(',', $categoryIds) . ')');
        $query->orderBy('id_category DESC, id_b2b_discount_rule DESC');

        $rows = Db::getInstance()->executeS($query);
        if (!is_array($rows)) {
            return;
        }

        $rulesByGroup = [];
        foreach ($rows as $row) {
            $idGroup = (int) $row['id_group'];
            if (!isset($rulesByGroup[$idGroup])) {
                $rulesByGroup[$idGroup] = (float) $row['discount_percent'];
            }
        }

        foreach ($rulesByGroup as $idGroup => $discountPercent) {
            $this->replaceGroupSpecificPrice($idProduct, (int) $idGroup, $discountPercent);
        }
    }

    private function replaceGroupSpecificPrice(int $idProduct, int $idGroup, float $discountPercent): void
    {
        Db::getInstance()->delete(
            'specific_price',
            'id_product = ' . (int) $idProduct .
            ' AND id_group = ' . (int) $idGroup .
            ' AND id_customer = 0' .
            ' AND id_product_attribute = 0' .
            ' AND from_quantity = 1' .
            " AND reduction_type = 'percentage'"
        );

        if ($discountPercent <= 0) {
            return;
        }

        $specificPrice = new SpecificPrice();
        $specificPrice->id_product = $idProduct;
        $specificPrice->id_product_attribute = 0;
        $specificPrice->id_shop = (int) Shop::getContextShopID();
        $specificPrice->id_shop_group = 0;
        $specificPrice->id_currency = 0;
        $specificPrice->id_country = 0;
        $specificPrice->id_group = $idGroup;
        $specificPrice->id_customer = 0;
        $specificPrice->price = -1;
        $specificPrice->from_quantity = 1;
        $specificPrice->reduction = $discountPercent / 100;
        $specificPrice->reduction_tax = 1;
        $specificPrice->reduction_type = 'percentage';
        $specificPrice->from = '0000-00-00 00:00:00';
        $specificPrice->to = '0000-00-00 00:00:00';

        if (!$specificPrice->add()) {
            throw new RuntimeException('Cannot create group specific price.');
        }
    }
}
