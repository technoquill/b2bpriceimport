<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Constant\ImportStatus;
use B2B\PriceImport\Repository\ImportRepository;
use RuntimeException;
use Throwable;

final class PriceImportProcessor
{
    public function __construct(
        private readonly ?ImportRepository $repository = null,
        private readonly ?ProductPriceUpdater $productPriceUpdater = null,
        private readonly ?AuditLogService $auditLogger = null
    ) {
    }

    public function process(int $idImport, int $limit = 500): array
    {
        $repository = $this->repository ?: new ImportRepository();
        $updater = $this->productPriceUpdater ?: new ProductPriceUpdater();
        $auditLogger = $this->auditLogger ?: new AuditLogService();
        $detailedProductLogging = $auditLogger->isDetailedProductLogging();

        if ($repository->find($idImport) === null) {
            throw new RuntimeException('Import not found.');
        }

        $limit = max(1, $limit);
        $repository->setStatus($idImport, ImportStatus::PROCESSING);

        $processed = 0;
        $errors = 0;
        $batches = 0;

        while (true) {
            $rows = $repository->getPendingStagingRows($idImport, $limit);

            if ($rows === []) {
                break;
            }

            $batches++;

            foreach ($rows as $row) {
                $idStaging = (int) $row['id_b2b_import_price_staging'];
                $idItem = (int) $row['id_b2b_import_item'];

                try {
                    $idProduct = (int) $row['id_product'];
                    $priceUah = (float) $row['price_uah'];
                    $active = $row['active'] !== null ? (int) $row['active'] : null;

                    $updater->applyDiscountMatrix($idProduct, $priceUah);
                    $updater->updateProduct($idProduct, $priceUah, $active);

                    $repository->markRowProcessed($idStaging, $idItem);
                    $processed++;
                } catch (Throwable $exception) {
                    $repository->markRowFailed($idStaging, $idItem, 'PROCESSING_ERROR', $exception->getMessage());
                    $errors++;
                }
            }
        }

        $repository->refreshStats($idImport);
        $repository->setStatus($idImport, $errors > 0 ? ImportStatus::FAILED : ImportStatus::FINISHED);

        return [
            'processed' => $processed,
            'failed' => $errors,
            'batches' => $batches,
            'has_more' => false,
        ];
    }

    public function processItem(int $idImport, int $idImportItem): array
    {
        $repository = $this->repository ?: new ImportRepository();

        if ($repository->find($idImport) === null) {
            throw new RuntimeException('Import not found.');
        }

        $row = $repository->getPendingStagingRowForItem($idImport, $idImportItem);

        if ($row === null) {
            throw new RuntimeException('The import position is not ready for processing.');
        }

        $updater = $this->productPriceUpdater ?: new ProductPriceUpdater();
        $auditLogger = $this->auditLogger ?: new AuditLogService();
        $result = $this->processRow(
            $idImport,
            $row,
            $repository,
            $updater,
            $auditLogger,
            $auditLogger->isDetailedProductLogging()
        );
        $repository->refreshStats($idImport);

        return $result;
    }

    private function processRow(
        int $idImport,
        array $row,
        ImportRepository $repository,
        ProductPriceUpdater $updater,
        AuditLogService $auditLogger,
        bool $detailedProductLogging
    ): array {
        $idStaging = (int) $row['id_b2b_import_price_staging'];
        $idItem = (int) $row['id_b2b_import_item'];
        $idProduct = (int) $row['id_product'];
        $before = null;

        try {
            $priceUah = (float) $row['price_uah'];
            $active = $row['active'] !== null ? (int) $row['active'] : null;

            if ($detailedProductLogging) {
                $before = $this->getProductAuditState($updater, $idProduct);
            }

            $updater->applyDiscountMatrix($idProduct, $priceUah);
            $updater->updateProduct($idProduct, $priceUah, $active);

            if ($detailedProductLogging) {
                $auditLogger->record(
                    'product.price_updated',
                    'product',
                    'success',
                    'Product prices updated by import.',
                    (string) $idProduct,
                    $before,
                    $this->getProductAuditState($updater, $idProduct),
                    [
                        'import_id' => $idImport,
                        'reference' => (string) ($row['reference'] ?? ''),
                        'id_import_item' => $idItem,
                    ]
                );
            }

            $repository->markRowProcessed($idStaging, $idItem);

            return ['processed' => 1, 'failed' => 0, 'error' => null];
        } catch (Throwable $exception) {
            $repository->markRowFailed($idStaging, $idItem, 'PROCESSING_ERROR', $exception->getMessage());

            if ($detailedProductLogging) {
                $auditLogger->record(
                    'product.update_failed',
                    'product',
                    'error',
                    $exception->getMessage(),
                    $idProduct > 0 ? (string) $idProduct : null,
                    $before,
                    null,
                    [
                        'import_id' => $idImport,
                        'reference' => (string) ($row['reference'] ?? ''),
                        'id_import_item' => $idItem,
                    ]
                );
            }

            return ['processed' => 0, 'failed' => 1, 'error' => $exception->getMessage()];
        }
    }

    private function getProductAuditState(ProductPriceUpdater $updater, int $idProduct): ?array
    {
        try {
            return $updater->getAuditState($idProduct);
        } catch (Throwable) {
            return null;
        }
    }
}
