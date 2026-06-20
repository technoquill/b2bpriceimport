<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Dto\ImportCreateData;
use RuntimeException;

final class ImportFileStorageService
{
    public function __construct(private readonly ?string $baseDirectory = null)
    {
    }

    public function storeUploadedCsv(array $file, ?int $createdBy): ImportCreateData
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('CSV upload failed.');
        }

        $originalFilename = basename((string) ($file['name'] ?? ''));
        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($originalFilename === '' || $tmpName === '') {
            throw new RuntimeException('Invalid uploaded CSV file.');
        }

        if (strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION)) !== 'csv') {
            throw new RuntimeException('Only .csv files are allowed.');
        }

        $directory = $this->baseDirectory ?: _PS_MODULE_DIR_ . 'b2bpriceimport/var/imports';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create import storage directory.');
        }

        $storedFilename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.csv';
        $targetPath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedFilename;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Cannot store uploaded CSV file.');
        }

        $size = filesize($targetPath);
        $hash = hash_file('sha256', $targetPath);

        return new ImportCreateData(
            name: pathinfo($originalFilename, PATHINFO_FILENAME),
            source: 'csv',
            originalFilename: $originalFilename,
            storedFilename: $storedFilename,
            filePath: $targetPath,
            fileSize: $size !== false ? (int) $size : null,
            fileHash: $hash !== false ? $hash : null,
            createdBy: $createdBy
        );
    }

    public function deleteStoredFile(?string $filePath): void
    {
        $filePath = trim((string) $filePath);

        if ($filePath === '' || !is_file($filePath)) {
            return;
        }

        $realFilePath = realpath($filePath);
        $realBasePath = realpath($this->baseDirectory ?: _PS_MODULE_DIR_ . 'b2bpriceimport/var/imports');

        if ($realFilePath === false || $realBasePath === false) {
            throw new RuntimeException('Cannot resolve import file path.');
        }

        $allowedPrefix = rtrim($realBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (strpos($realFilePath, $allowedPrefix) !== 0) {
            throw new RuntimeException('Import file path is outside the import storage directory.');
        }

        if (!unlink($realFilePath)) {
            throw new RuntimeException('Cannot delete import file.');
        }
    }
}
