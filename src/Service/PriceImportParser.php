<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Constant\ImportStatus;
use B2B\PriceImport\Repository\ImportRepository;
use League\Csv\Reader;
use Product;
use RuntimeException;
use Throwable;

final class PriceImportParser
{
    public function __construct(
        private readonly ?ImportRepository $repository = null
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

        $parsed = 0;
        $valid = 0;
        $failed = 0;

        foreach ($reader->getRecords() as $offset => $record) {
            $rowNumber = (int) $offset + 2;
            $parsed++;

            try {
                $normalized = $this->normalize($record);
                $idProduct = (int) Product::getIdByReference($normalized['reference']);

                if ($idProduct <= 0) {
                    throw new RuntimeException('Product not found by reference: ' . $normalized['reference']);
                }

                $priceUah = round($normalized['price'] * $normalized['currency_rate'], 6);

                $idItem = $repository->addItem($idImport, $rowNumber, $normalized['reference'], $normalized, 'pending');

                $repository->addPriceStaging([
                    'id_b2b_import' => $idImport,
                    'id_b2b_import_item' => $idItem,
                    'reference' => $normalized['reference'],
                    'id_product' => $idProduct,
                    'source_price' => $normalized['price'],
                    'currency_code' => $normalized['currency'],
                    'currency_rate' => $normalized['currency_rate'],
                    'price_uah' => $priceUah,
                    'active' => $normalized['active'],
                    'validation_status' => 'valid',
                    'processing_status' => 'pending',
                ]);

                $valid++;
            } catch (Throwable $exception) {
                $reference = isset($record['reference']) ? trim((string) $record['reference']) : null;
                $reference = $reference !== '' ? $reference : null;

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

        $repository->update($idImport, [
            'total_rows' => $parsed,
            'parsed_rows' => $parsed,
            'validated_rows' => $valid,
            'failed_rows' => $failed,
            'last_row_number' => $parsed + 1,
        ]);
        $repository->setStatus($idImport, ImportStatus::PARSED);

        return ['parsed' => $parsed, 'valid' => $valid, 'failed' => $failed];
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

        foreach (['reference', 'price', 'currency', 'currency_rate', 'active'] as $column) {
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
            'price' => $price,
            'currency' => $currency,
            'currency_rate' => $currencyRate,
            'active' => (int) $activeRaw,
        ];
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
