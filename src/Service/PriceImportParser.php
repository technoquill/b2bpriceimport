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
        private readonly ?ImportRepository $repository = null,
        private readonly ?CurrencyRateResolver $currencyRateResolver = null
    ) {
    }

    public function parse(int $idImport): array
    {
        $repository = $this->repository ?: new ImportRepository();
        $currencyRateResolver = $this->currencyRateResolver ?: new CurrencyRateResolver();
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

                $rate = $currencyRateResolver->getRateToUah($normalized['currency']);
                $priceUah = round($normalized['price'] * $rate, 6);

                $idItem = $repository->addItem($idImport, $rowNumber, $normalized['reference'], $normalized, 'pending');

                $repository->addPriceStaging([
                    'id_b2b_import' => $idImport,
                    'id_b2b_import_item' => $idItem,
                    'reference' => $normalized['reference'],
                    'id_product' => $idProduct,
                    'source_price' => $normalized['price'],
                    'currency_code' => $normalized['currency'],
                    'currency_rate' => $rate,
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

        foreach (['reference', 'price', 'currency'] as $column) {
            if (!in_array($column, $header, true)) {
                throw new RuntimeException('Missing CSV column: ' . $column);
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

        $priceRaw = str_replace([' ', ','], ['', '.'], trim((string) ($row['price'] ?? '')));
        if ($priceRaw === '' || !is_numeric($priceRaw)) {
            throw new RuntimeException('Invalid price for reference: ' . $reference);
        }

        $price = (float) $priceRaw;
        if ($price < 0) {
            throw new RuntimeException('Negative price for reference: ' . $reference);
        }

        $currency = strtoupper(trim((string) ($row['currency'] ?? 'UAH')));
        $active = null;
        if (array_key_exists('active', $row) && trim((string) $row['active']) !== '') {
            $active = (int) ((int) $row['active'] > 0);
        }

        return [
            'reference' => $reference,
            'price' => $price,
            'currency' => $currency !== '' ? $currency : 'UAH',
            'active' => $active,
        ];
    }
}
