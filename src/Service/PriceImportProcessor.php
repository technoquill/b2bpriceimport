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
        private readonly ?ProductPriceUpdater $productPriceUpdater = null
    ) {
    }

    public function process(int $idImport, int $limit = 500): array
    {
        $repository = $this->repository ?: new ImportRepository();
        $updater = $this->productPriceUpdater ?: new ProductPriceUpdater();

        if ($repository->find($idImport) === null) {
            throw new RuntimeException('Import not found.');
        }

        $repository->setStatus($idImport, ImportStatus::PROCESSING);

        $processed = 0;
        $errors = 0;

        foreach ($repository->getPendingStagingRows($idImport, $limit) as $row) {
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

        $repository->refreshStats($idImport);
        $repository->setStatus($idImport, $errors > 0 ? ImportStatus::FAILED : ImportStatus::FINISHED);

        return ['processed' => $processed, 'failed' => $errors];
    }
}
