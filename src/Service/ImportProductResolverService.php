<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Constant\ImportStatus;
use B2B\PriceImport\Repository\ImportRepository;
use B2B\PriceImport\Repository\ProductMappingRepository;
use Db;
use RuntimeException;
use Shop;

final class ImportProductResolverService
{
    public const ACTION_CREATE = 'create';
    public const ACTION_LINK = 'link';
    public const ACTION_SKIP = 'skip';

    public function __construct(
        private readonly ?ImportRepository $repository = null,
        private readonly ?ProductMappingRepository $mappingRepository = null,
        private readonly ?PriceImportProcessor $processor = null,
        private readonly ?AuditLogService $auditLogger = null,
        private readonly ?ImportedProductCreatorService $productCreator = null
    ) {
    }

    public function resolve(
        int $idImport,
        int $idImportItem,
        string $action,
        ?int $idProduct = null,
        ?int $employeeId = null
    ): array {
        $repository = $this->repository ?: new ImportRepository();
        $import = $repository->find($idImport);

        if ($import === null) {
            throw new RuntimeException('Import not found.');
        }

        if (in_array((string) ($import['status'] ?? ''), ImportStatus::activeStatuses(), true)) {
            throw new RuntimeException('Wait until the import has finished before resolving this position.');
        }

        $item = $repository->findImportItemWithStaging($idImport, $idImportItem);

        if ($item === null) {
            throw new RuntimeException('Import position not found.');
        }

        if (!$this->isResolvable($item)) {
            throw new RuntimeException('This import position no longer requires product resolution.');
        }

        if ($action === self::ACTION_SKIP) {
            $repository->markImportItemSkipped($idImportItem);
            $repository->refreshStats($idImport);

            $this->getAuditLogger()->record(
                'import_item.skipped',
                'import',
                'success',
                'Unmatched import position skipped.',
                (string) $idImport,
                $this->buildItemAuditState($item),
                ['status' => 'skipped'],
                [
                    'id_import_item' => $idImportItem,
                    'reference' => (string) $item['reference'],
                ],
                'admin'
            );

            return [
                'action' => self::ACTION_SKIP,
                'message' => 'The import position was skipped.',
                'id_product' => null,
                'processing_warning' => null,
            ];
        }

        $mappingRepository = $this->mappingRepository ?: new ProductMappingRepository();

        if ($action === self::ACTION_CREATE) {
            $createdProduct = ($this->productCreator ?: new ImportedProductCreatorService())->createInactive(
                (string) $item['reference'],
                (string) ($item['product_name'] ?? ''),
                (float) ($item['price_uah'] ?? 0)
            );
            $idProduct = $createdProduct['id_product'];
            $mappingRepository->save((string) $item['reference'], $idProduct, $employeeId);
            $repository->markImportItemCreated($idImportItem, $idProduct);
            $repository->refreshStats($idImport);

            $this->getAuditLogger()->record(
                'product.created_from_import',
                'product',
                'success',
                'Inactive product created from an unmatched import position.',
                (string) $idProduct,
                null,
                [
                    'reference' => (string) $item['reference'],
                    'name' => $createdProduct['name'],
                    'price' => (float) $item['price_uah'],
                    'active' => 0,
                ],
                [
                    'import_id' => $idImport,
                    'id_import_item' => $idImportItem,
                    'creation_mode' => 'manual',
                ],
                'admin'
            );

            return [
                'action' => self::ACTION_CREATE,
                'message' => 'The product was created in a disabled state.',
                'id_product' => $idProduct,
                'processing_warning' => null,
            ];
        }

        if ($action !== self::ACTION_LINK) {
            throw new RuntimeException('Unknown product resolution action.');
        }

        $idProduct = (int) $idProduct;

        if (!$this->productExists($idProduct)) {
            throw new RuntimeException('Select an existing product.');
        }

        $mappingRepository->save((string) $item['reference'], $idProduct, $employeeId);
        $repository->prepareImportItemForProcessing($idImportItem, $idProduct);
        $processingResult = ($this->processor ?: new PriceImportProcessor())->processItem(
            $idImport,
            $idImportItem
        );

        $processingWarning = $processingResult['failed'] > 0
            ? (string) ($processingResult['error'] ?? 'The product was linked, but the imported values could not be applied.')
            : null;

        if ($processingWarning !== null) {
            $repository->setStatus($idImport, ImportStatus::FAILED, $processingWarning);
        }

        $this->getAuditLogger()->record(
            'import_item.product_linked',
            'import',
            $processingWarning === null ? 'success' : 'warning',
            $processingWarning === null
                ? 'Import position linked to an existing product and processed.'
                : 'Import position linked to an existing product, but processing failed.',
            (string) $idImport,
            $this->buildItemAuditState($item),
            [
                'id_product' => $idProduct,
                'status' => $processingWarning === null ? 'processed' : 'failed',
            ],
            [
                'id_import_item' => $idImportItem,
                'reference' => (string) $item['reference'],
                'processing_error' => $processingWarning,
            ],
            'admin'
        );

        return [
            'action' => self::ACTION_LINK,
            'message' => $processingWarning === null
                ? 'The product was linked and the import position was processed.'
                : 'The product was linked, but the imported values could not be applied.',
            'id_product' => $idProduct,
            'processing_warning' => $processingWarning,
        ];
    }

    private function isResolvable(array $item): bool
    {
        $errorCode = (string) ($item['staging_error_code'] ?: $item['error_code']);

        return (int) ($item['id_product'] ?? 0) <= 0
            && $errorCode === 'PRODUCT_NOT_FOUND'
            && (string) ($item['status'] ?? '') === 'unmatched';
    }

    private function productExists(int $idProduct): bool
    {
        if ($idProduct <= 0) {
            return false;
        }

        $idShop = (int) Shop::getContextShopID();
        $sql = 'SELECT p.id_product
                FROM `' . _DB_PREFIX_ . 'product` p';

        if ($idShop > 0) {
            $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                        ON ps.id_product = p.id_product
                       AND ps.id_shop = ' . $idShop;
        }

        $sql .= ' WHERE p.id_product = ' . $idProduct;

        return (int) Db::getInstance()->getValue($sql) > 0;
    }

    private function buildItemAuditState(array $item): array
    {
        return [
            'id_import' => (int) $item['id_b2b_import'],
            'reference' => (string) $item['reference'],
            'product_name' => (string) ($item['product_name'] ?? ''),
            'status' => (string) $item['status'],
            'id_product' => (int) ($item['id_product'] ?? 0),
        ];
    }

    private function getAuditLogger(): AuditLogService
    {
        return $this->auditLogger ?: new AuditLogService();
    }
}
