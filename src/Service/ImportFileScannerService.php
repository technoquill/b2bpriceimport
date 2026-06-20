<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Dto\ImportCreateData;
use B2B\PriceImport\Repository\ImportRepository;
use DirectoryIterator;
use RuntimeException;
use SplFileInfo;

final class ImportFileScannerService
{
    public function __construct(private readonly ?ImportRepository $repository = null)
    {
    }

    public function scanAndCreateImports(string $directory, int $maxFileAgeHours = 24, int $limit = 1): array
    {
        $repository = $this->repository ?: new ImportRepository();
        $directory = rtrim(trim($directory), DIRECTORY_SEPARATOR);
        $maxFileAgeHours = max(1, $maxFileAgeHours);
        $limit = max(1, $limit);

        if ($directory === '') {
            throw new RuntimeException('Scan directory is required.');
        }

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Cannot create scan directory.');
            }
        }

        $realDirectory = realpath($directory);
        if ($realDirectory === false) {
            throw new RuntimeException('Cannot resolve scan directory.');
        }

        $cutoffTimestamp = time() - ($maxFileAgeHours * 3600);
        $created = [];
        $skipped = [];

        foreach (new DirectoryIterator($realDirectory) as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'csv') {
                continue;
            }

            $filePath = $file->getRealPath();
            if ($filePath === false) {
                $skipped[] = [
                    'file' => $file->getPathname(),
                    'reason' => 'unresolved_path',
                ];
                continue;
            }

            if (!$file->isReadable()) {
                $skipped[] = [
                    'file' => $filePath,
                    'reason' => 'not_readable',
                ];
                continue;
            }

            if ($file->getMTime() < $cutoffTimestamp) {
                $skipped[] = [
                    'file' => $filePath,
                    'reason' => 'older_than_allowed_age',
                ];
                continue;
            }

            $hash = hash_file('sha256', $filePath);
            if ($hash === false) {
                $skipped[] = [
                    'file' => $filePath,
                    'reason' => 'hash_failed',
                ];
                continue;
            }

            if ($repository->findByFileHash($hash) !== null || $repository->findByFilePath($filePath) !== null) {
                $skipped[] = [
                    'file' => $filePath,
                    'reason' => 'already_registered',
                ];
                continue;
            }

            $idImport = $repository->create(new ImportCreateData(
                name: pathinfo($file->getFilename(), PATHINFO_FILENAME),
                source: 'csv',
                originalFilename: $file->getFilename(),
                storedFilename: $file->getFilename(),
                filePath: $filePath,
                fileSize: $file->getSize(),
                fileHash: $hash,
                createdBy: null
            ));

            $repository->createJob($idImport, 'parse');
            $repository->createJob($idImport, 'process');

            $created[] = [
                'id_import' => $idImport,
                'file' => $filePath,
                'hash' => $hash,
            ];

            if (count($created) >= $limit) {
                break;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }
}
