<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\DTO\ImportCreateData;
use DirectoryIterator;
use RuntimeException;

final class ImportFileStorageService
{
    private const MODULE_NAME = 'b2bpriceimport';
    private const IMPORT_RELATIVE_DIRECTORY = 'var/imports';

    public function __construct(private readonly ?string $baseDirectory = null)
    {
    }

    public static function getDefaultDirectory(): string
    {
        $moduleDirectory = rtrim((string) _PS_MODULE_DIR_, '/\\')
            . DIRECTORY_SEPARATOR
            . self::MODULE_NAME;

        return self::getDirectoryForModule($moduleDirectory);
    }

    public static function getDirectoryForModule(string $moduleDirectory): string
    {
        return rtrim($moduleDirectory, '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, self::IMPORT_RELATIVE_DIRECTORY);
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

        $directory = $this->getBaseDirectory();
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

    public function listStoredCsvFiles(): array
    {
        $directory = $this->getBaseDirectory();

        if (!is_dir($directory) || !is_readable($directory)) {
            return [];
        }

        $realBasePath = realpath($directory);

        if ($realBasePath === false) {
            return [];
        }

        $allowedPrefix = rtrim($realBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $files = [];

        foreach (new DirectoryIterator($realBasePath) as $file) {
            if (
                $file->isDot()
                || !$file->isFile()
                || !$file->isReadable()
                || strtolower($file->getExtension()) !== 'csv'
            ) {
                continue;
            }

            $realFilePath = $file->getRealPath();

            if ($realFilePath === false || strpos($realFilePath, $allowedPrefix) !== 0) {
                continue;
            }

            $files[] = [
                'stored_filename' => $file->getFilename(),
                'file_path' => $realFilePath,
                'file_size' => $file->getSize(),
                'modified_at' => $file->getMTime(),
            ];
        }

        usort($files, static function (array $left, array $right): int {
            $modifiedComparison = ((int) $right['modified_at']) <=> ((int) $left['modified_at']);

            if ($modifiedComparison !== 0) {
                return $modifiedComparison;
            }

            return strcmp((string) $left['stored_filename'], (string) $right['stored_filename']);
        });

        return $files;
    }

    public function createDataFromStoredCsv(string $storedFilename, ?int $createdBy): ImportCreateData
    {
        $storedFilename = trim($storedFilename);

        if (
            $storedFilename === ''
            || basename($storedFilename) !== $storedFilename
            || strpos($storedFilename, '\\') !== false
            || strtolower((string) pathinfo($storedFilename, PATHINFO_EXTENSION)) !== 'csv'
        ) {
            throw new RuntimeException('Invalid stored CSV file.');
        }

        $realBasePath = realpath($this->getBaseDirectory());
        $candidatePath = $realBasePath !== false
            ? $realBasePath . DIRECTORY_SEPARATOR . $storedFilename
            : '';
        $realFilePath = $candidatePath !== '' ? realpath($candidatePath) : false;

        if ($realBasePath === false || $realFilePath === false) {
            throw new RuntimeException('Stored CSV file not found.');
        }

        $allowedPrefix = rtrim($realBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (
            strpos($realFilePath, $allowedPrefix) !== 0
            || !is_file($realFilePath)
            || !is_readable($realFilePath)
        ) {
            throw new RuntimeException('Stored CSV file is not available.');
        }

        $size = filesize($realFilePath);
        $hash = hash_file('sha256', $realFilePath);

        if ($hash === false) {
            throw new RuntimeException('Cannot calculate stored CSV file hash.');
        }

        return new ImportCreateData(
            name: pathinfo($storedFilename, PATHINFO_FILENAME),
            source: 'csv',
            originalFilename: $storedFilename,
            storedFilename: $storedFilename,
            filePath: $realFilePath,
            fileSize: $size !== false ? (int) $size : null,
            fileHash: $hash,
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
        $realBasePath = realpath($this->getBaseDirectory());

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

    private function getBaseDirectory(): string
    {
        return $this->baseDirectory ?: self::getDefaultDirectory();
    }
}
