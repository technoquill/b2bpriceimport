<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Constant\ImportStatus;
use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;
use B2B\PriceImport\Repository\ImportRepository;
use B2B\PriceImport\Repository\ProductMappingRepository;
use League\Csv\Reader;
use Product;
use RuntimeException;
use Throwable;

final class PriceImportParser
{
    public function __construct(
        private readonly ?ImportRepository $repository = null,
        private readonly ?ProductMappingRepository $productMappingRepository = null,
        private readonly ?B2BPriceImportConfigRepository $configRepository = null,
        private readonly ?ImportedProductCreatorService $productCreator = null,
        private readonly ?AuditLogService $auditLogger = null
    ) {
    }

    public function parse(int $idImport): array
    {
        $repository = $this->repository ?: new ImportRepository();
        $import = $repository->find($idImport);

        if ($import === null) {
            throw new RuntimeException('Import not found.');
        }

        $filePath = (string) ($import['file_path'] ?? '');
        if ($filePath === '' || !is_file($filePath)) {
            throw new RuntimeException('Import file not found.');
        }

        $repository->resetRows($idImport);
        $repository->setStatus($idImport, ImportStatus::PARSING);

        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setDelimiter($this->detectDelimiter($filePath));
        $reader->setHeaderOffset(0);

        $header = $reader->getHeader();
        $this->assertHeader($header);
        $repository->update($idImport, ['header_json' => json_encode($header, JSON_UNESCAPED_UNICODE)]);

        $mappingRepository = $this->productMappingRepository ?: new ProductMappingRepository();
        $autoCreateUnknownProducts = ($this->configRepository ?: new B2BPriceImportConfigRepository())
            ->shouldAutoCreateUnknownProducts();
        $parsed = 0;
        $valid = 0;
        $warnings = 0;
        $failed = 0;
        $created = 0;
        $autoCreatedProductIds = [];

        foreach ($reader->getRecords() as $offset => $record) {
            $rowNumber = (int) $offset + 2;
            $parsed++;

            try {
                $normalized = $this->normalize($record);
                $priceUah = round($normalized['price'] * $normalized['currency_rate'], 6);
                $referenceKey = 'reference:' . strtolower($normalized['reference']);
                $autoCreatedInThisImport = isset($autoCreatedProductIds[$referenceKey]);
                $idProduct = $autoCreatedInThisImport
                    ? $autoCreatedProductIds[$referenceKey]
                    : $this->resolveProductId($normalized['reference'], $mappingRepository);

                if ($idProduct <= 0) {
                    $message = 'Product not found by reference: ' . $normalized['reference'];
                    $idItem = $repository->addItem(
                        $idImport,
                        $rowNumber,
                        $normalized['reference'],
                        $normalized,
                        'unmatched',
                        'PRODUCT_NOT_FOUND',
                        $message
                    );

                    $repository->addPriceStaging([
                        'id_b2b_import' => $idImport,
                        'id_b2b_import_item' => $idItem,
                        'reference' => $normalized['reference'],
                        'product_name' => $normalized['name'],
                        'id_product' => null,
                        'source_price' => $normalized['price'],
                        'currency_code' => $normalized['currency'],
                        'currency_rate' => $normalized['currency_rate'],
                        'price_uah' => $priceUah,
                        'active' => $normalized['active'],
                        'validation_status' => 'unmatched',
                        'processing_status' => 'waiting_product',
                        'error_code' => 'PRODUCT_NOT_FOUND',
                        'error_message' => $message,
                    ]);

                    if (!$autoCreateUnknownProducts) {
                        $warnings++;

                        continue;
                    }

                    try {
                        $createdProduct = ($this->productCreator ?: new ImportedProductCreatorService())
                            ->createInactive(
                                $normalized['reference'],
                                $normalized['name'],
                                $priceUah
                            );
                        $idProduct = $createdProduct['id_product'];
                        $mappingRepository->save($normalized['reference'], $idProduct);
                        $repository->markImportItemCreated($idItem, $idProduct);
                        $autoCreatedProductIds[$referenceKey] = $idProduct;
                        $this->getAuditLogger()->record(
                            'product.created_from_import',
                            'product',
                            'success',
                            'Inactive product automatically created from an unmatched import position.',
                            (string) $idProduct,
                            null,
                            [
                                'reference' => $normalized['reference'],
                                'name' => $createdProduct['name'],
                                'price' => $priceUah,
                                'active' => 0,
                            ],
                            [
                                'import_id' => $idImport,
                                'id_import_item' => $idItem,
                                'creation_mode' => 'automatic',
                            ]
                        );

                        $valid++;
                        $created++;
                    } catch (Throwable $creationException) {
                        $warnings++;
                        $this->getAuditLogger()->record(
                            'product.auto_create_failed',
                            'import',
                            'error',
                            'Automatic draft product creation failed; the import position remains unmatched.',
                            (string) $idImport,
                            null,
                            null,
                            [
                                'id_import_item' => $idItem,
                                'reference' => $normalized['reference'],
                                'creation_mode' => 'automatic',
                                'error' => $creationException->getMessage(),
                            ]
                        );
                    }

                    continue;
                }

                $idItem = $repository->addItem($idImport, $rowNumber, $normalized['reference'], $normalized, 'pending');

                $repository->addPriceStaging([
                    'id_b2b_import' => $idImport,
                    'id_b2b_import_item' => $idItem,
                    'reference' => $normalized['reference'],
                    'product_name' => $normalized['name'],
                    'id_product' => $idProduct,
                    'source_price' => $normalized['price'],
                    'currency_code' => $normalized['currency'],
                    'currency_rate' => $normalized['currency_rate'],
                    'price_uah' => $priceUah,
                    'active' => $normalized['active'],
                    'validation_status' => 'valid',
                    'processing_status' => 'pending',
                ]);

                if ($autoCreatedInThisImport) {
                    $repository->markImportItemCreated($idItem, $idProduct);
                }

                $valid++;
            } catch (Throwable $exception) {
                $reference = $this->getRecordValue($record, 'reference');
                $reference = $reference !== '' ? $reference : null;
                $productName = $this->getRecordValue($record, 'name');
                $productName = $productName !== '' ? $productName : null;

                $idItem = $repository->addItem(
                    $idImport,
                    $rowNumber,
                    $reference,
                    $record,
                    'failed',
                    'VALIDATION_ERROR',
                    $exception->getMessage()
                );

                $repository->addPriceStaging([
                    'id_b2b_import' => $idImport,
                    'id_b2b_import_item' => $idItem,
                    'reference' => $reference ?: 'row_' . $rowNumber,
                    'product_name' => $productName,
                    'id_product' => null,
                    'source_price' => null,
                    'currency_code' => null,
                    'currency_rate' => null,
                    'price_uah' => null,
                    'active' => null,
                    'validation_status' => 'failed',
                    'processing_status' => 'failed',
                    'error_code' => 'VALIDATION_ERROR',
                    'error_message' => $exception->getMessage(),
                ]);

                $failed++;
            }
        }

        $repository->update($idImport, ['last_row_number' => $parsed + 1]);
        $repository->refreshStats($idImport);
        $repository->setStatus($idImport, ImportStatus::PARSED);

        return [
            'parsed' => $parsed,
            'valid' => $valid,
            'created' => $created,
            'warnings' => $warnings,
            'failed' => $failed,
        ];
    }

    private function resolveProductId(
        string $reference,
        ProductMappingRepository $mappingRepository
    ): int
    {
        $idProduct = (int) Product::getIdByReference($reference);

        if ($idProduct > 0) {
            return $idProduct;
        }

        return (int) ($mappingRepository->findProductId($reference) ?? 0);
    }

    private function getAuditLogger(): AuditLogService
    {
        return $this->auditLogger ?: new AuditLogService();
    }

    private function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'rb');
        $line = $handle !== false ? (string) fgets($handle) : '';
        if (is_resource($handle)) {
            fclose($handle);
        }

        return substr_count($line, ';') >= substr_count($line, ',') ? ';' : ',';
    }

    private function assertHeader(array $header): void
    {
        $header = array_map(static fn ($value): string => strtolower(trim((string) $value)), $header);

        foreach (['reference', 'price', 'currency', 'currency_rate', 'active', 'name'] as $column) {
            if (!in_array($column, $header, true)) {
                throw new RuntimeException('Missing required CSV column: ' . $column);
            }
        }
    }

    private function normalize(array $record): array
    {
        $row = [];
        foreach ($record as $key => $value) {
            $row[strtolower(trim((string) $key))] = $value;
        }

        $reference = trim((string) ($row['reference'] ?? ''));
        if ($reference === '') {
            throw new RuntimeException('Reference is empty.');
        }

        $productName = trim((string) ($row['name'] ?? ''));

        $price = $this->normalizeDecimal($row['price'] ?? null, 'price', $reference, true);
        $currencyRate = $this->normalizeDecimal($row['currency_rate'] ?? null, 'currency_rate', $reference, false);

        $currency = strtoupper(trim((string) ($row['currency'] ?? '')));
        if ($currency === '') {
            throw new RuntimeException('Currency is empty for reference: ' . $reference);
        }

        if (strlen($currency) !== 3 || ctype_alpha($currency) === false) {
            throw new RuntimeException('Invalid currency code for reference: ' . $reference);
        }

        $activeRaw = trim((string) ($row['active'] ?? ''));
        if ($activeRaw === '') {
            throw new RuntimeException('Active is empty for reference: ' . $reference);
        }

        if (!in_array($activeRaw, ['0', '1'], true)) {
            throw new RuntimeException('Active must be 0 or 1 for reference: ' . $reference);
        }

        return [
            'reference' => $reference,
            'name' => $productName,
            'price' => $price,
            'currency' => $currency,
            'currency_rate' => $currencyRate,
            'active' => (int) $activeRaw,
        ];
    }

    private function getRecordValue(array $record, string $column): string
    {
        foreach ($record as $key => $value) {
            if (strtolower(trim((string) $key)) === $column) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function normalizeDecimal($value, string $fieldName, string $reference, bool $allowZero): float
    {
        $raw = str_replace([' ', ','], ['', '.'], trim((string) $value));

        if ($raw === '' || !is_numeric($raw)) {
            throw new RuntimeException('Invalid ' . $fieldName . ' for reference: ' . $reference);
        }

        $number = (float) $raw;

        if ($allowZero) {
            if ($number < 0) {
                throw new RuntimeException($fieldName . ' cannot be negative for reference: ' . $reference);
            }
        } elseif ($number <= 0) {
            throw new RuntimeException($fieldName . ' must be greater than zero for reference: ' . $reference);
        }

        return $number;
    }
}
