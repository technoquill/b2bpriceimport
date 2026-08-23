<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use Configuration;
use Language;
use Product;
use RuntimeException;
use Shop;
use Tools;

final class ImportedProductCreatorService
{
    /**
     * @return array{id_product: int, name: string}
     */
    public function createInactive(string $reference, string $productName, float $priceUah): array
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new RuntimeException('Product reference is empty.');
        }

        if (Tools::strlen($reference) > 64) {
            throw new RuntimeException('Product reference is longer than the 64 characters supported by PrestaShop.');
        }

        if ((int) Product::getIdByReference($reference) > 0) {
            throw new RuntimeException('A product with this reference already exists. Link it instead.');
        }

        $name = $this->normalizeProductName($productName, $reference);
        $slug = Tools::link_rewrite($name);

        if ($slug === '') {
            $slug = 'product-' . substr(sha1($reference), 0, 12);
        }

        $idCategory = (int) Configuration::get('PS_HOME_CATEGORY');

        if ($idCategory <= 0) {
            $idCategory = 2;
        }

        $product = new Product();
        $product->reference = $reference;
        $product->price = max(0, $priceUah);
        $product->active = 0;
        $product->visibility = 'both';
        $product->available_for_order = 1;
        $product->show_price = 1;
        $product->indexed = 0;
        $product->id_category_default = $idCategory;
        $idShop = (int) Shop::getContextShopID();
        $product->id_shop_default = $idShop > 0
            ? $idShop
            : (int) Configuration::get('PS_SHOP_DEFAULT');
        $product->name = [];
        $product->link_rewrite = [];

        foreach (Language::getLanguages(false) as $language) {
            $idLanguage = (int) $language['id_lang'];
            $product->name[$idLanguage] = $name;
            $product->link_rewrite[$idLanguage] = $slug;
        }

        if (!$product->add()) {
            throw new RuntimeException('Cannot create product.');
        }

        if (!$product->addToCategories([$idCategory])) {
            throw new RuntimeException('The product was created, but its default category could not be assigned.');
        }

        return [
            'id_product' => (int) $product->id,
            'name' => $name,
        ];
    }

    private function normalizeProductName(string $productName, string $reference): string
    {
        $name = trim($productName);

        if ($name === '') {
            $name = trim($reference);
        }

        $name = Tools::substr($name, 0, 128);

        return $name !== '' ? $name : 'Imported product';
    }
}
