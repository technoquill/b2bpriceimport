<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

final class ImportFileStorage
{
    public function __construct(
        private readonly string $moduleDir
    ) {
    }

    public function storeUploadedFile(array $file): StoredImportFile
    {
        if (!isset($file['tmp_name'], $file['name'], $file['size'], $file['error'])) {
            throw new \InvalidArgumentException('Invalid upload payload.');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed. Upload error code: ' . (int) $file['error']);
        }

        $originalName = (string) $file['name'];
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            throw new \RuntimeException('Only CSV files are allowed.');
        }

        $importDir = rtrim($this->moduleDir, '/\\') . '/var/imports';

        if (!is_dir($importDir) && !mkdir($importDir, 0755, true) && !is_dir($importDir)) {
            throw new \RuntimeException('Cannot create import directory.');
        }

        $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.csv';
        $targetPath = $importDir . '/' . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Cannot move uploaded file.');
        }

        $hash = hash_file('sha256', $targetPath);

        if ($hash === false) {
            throw new \RuntimeException('Cannot calculate file hash.');
        }

        return new StoredImportFile(
            originalFilename: $originalName,
            storedFilename: $storedName,
            filePath: $targetPath,
            fileSize: (int) $file['size'],
            fileHash: $hash
        );
    }
}