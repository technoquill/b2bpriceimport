<?php

declare(strict_types=1);

namespace B2B\PriceImport\Repository;

use Db;
use DbQuery;
use RuntimeException;

final class ProductMappingRepository
{
    public function findProductId(string $reference): ?int
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        $query = new DbQuery();
        $query->select('pm.id_product');
        $query->from('b2b_import_product_mapping', 'pm');
        $query->innerJoin('product', 'p', 'p.id_product = pm.id_product');
        $query->where("pm.reference = '" . pSQL($reference) . "'");

        $idProduct = (int) Db::getInstance()->getValue($query);

        return $idProduct > 0 ? $idProduct : null;
    }

    public function save(string $reference, int $idProduct, ?int $createdBy = null): void
    {
        $reference = trim($reference);

        if ($reference === '' || $idProduct <= 0) {
            throw new RuntimeException('Invalid product mapping.');
        }

        $now = date('Y-m-d H:i:s');
        $existingId = (int) Db::getInstance()->getValue(
            'SELECT id_b2b_import_product_mapping
             FROM `' . _DB_PREFIX_ . "b2b_import_product_mapping`
             WHERE reference = '" . pSQL($reference) . "'"
        );

        if ($existingId > 0) {
            $saved = Db::getInstance()->update(
                'b2b_import_product_mapping',
                [
                    'id_product' => $idProduct,
                    'created_by' => $createdBy,
                    'date_upd' => $now,
                ],
                'id_b2b_import_product_mapping = ' . $existingId,
                0,
                true
            );
        } else {
            $saved = Db::getInstance()->insert('b2b_import_product_mapping', [
                'reference' => pSQL($reference),
                'id_product' => $idProduct,
                'created_by' => $createdBy,
                'date_add' => $now,
                'date_upd' => $now,
            ], true);
        }

        if (!$saved) {
            throw new RuntimeException('Cannot save product mapping.');
        }
    }
}
