<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\DTO\ImportCreateData;
use B2B\PriceImport\Repository\ImportRepository;
use DirectoryIterator;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;

final class ImportFileScannerService
{
    public function __construct(
        private readonly ?ImportRepository $repository = null,
        private readonly ?AuditLogService $auditLogger = null
    ) {
    }

    public function scanAndCreateImports(
        string $directory,
        int $maxFileAgeHours = 24,
        int $limit = 1,
        ?string $requestedFilename = null
    ): array
    {
        $repository = $this->repository ?: new ImportRepository();
        $directory = rtrim(trim($directory), DIRECTORY_SEPARATOR);
        $maxFileAgeHours = max(1, $maxFileAgeHours);
        $limit = max(1, $limit);
        $requestedFilename = self::normalizeRequestedFilename($requestedFilename);

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
        $requestedFileFound = false;

        foreach (new DirectoryIterator($realDirectory) as $file) {
            if (
                $requestedFilename !== null
                && $file->getFilename() !== $requestedFilename
            ) {
                continue;
            }

            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            if ($requestedFilename !== null) {
                $requestedFileFound = true;
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

            // 1C may replace a price file while keeping the same filename. The
            // content hash identifies an already imported file; the path does
            // not, because a new file version can legitimately reuse it.
            if ($repository->findByFileHash($hash) !== null) {
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

        if ($requestedFilename !== null && !$requestedFileFound) {
            ($this->auditLogger ?: new AuditLogService())->record(
                'file.scan_failed',
                'file',
                'error',
                'Requested CSV file was not found by the scanner.',
                $requestedFilename,
                null,
                null,
                ['requested_filename' => $requestedFilename]
            );
            throw new RuntimeException('Requested CSV file was not found: ' . $requestedFilename);
        }

        $auditResult = !empty($skipped) ? 'warning' : 'success';
        ($this->auditLogger ?: new AuditLogService())->record(
            'file.scan_completed',
            'file',
            $auditResult,
            sprintf(
                'File scan completed: %d registered, %d skipped.',
                count($created),
                count($skipped)
            ),
            $requestedFilename,
            null,
            null,
            [
                'created' => array_map(
                    static fn (array $item): array => [
                        'id_import' => (int) ($item['id_import'] ?? 0),
                        'file' => basename((string) ($item['file'] ?? '')),
                        'hash' => (string) ($item['hash'] ?? ''),
                    ],
                    $created
                ),
                'skipped' => array_map(
                    static fn (array $item): array => [
                        'file' => basename((string) ($item['file'] ?? '')),
                        'reason' => (string) ($item['reason'] ?? 'unknown'),
                    ],
                    array_slice($skipped, 0, 100)
                ),
                'skipped_total' => count($skipped),
                'skipped_truncated' => count($skipped) > 100,
            ]
        );

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    public static function normalizeRequestedFilename(?string $requestedFilename): ?string
    {
        if ($requestedFilename === null || trim($requestedFilename) === '') {
            return null;
        }

        $requestedFilename = trim($requestedFilename);

        if (
            strlen($requestedFilename) > 255
            || preg_match('/[\/\\\\\x00-\x1F\x7F]/', $requestedFilename) === 1
            || in_array($requestedFilename, ['.', '..'], true)
            || trim((string) pathinfo($requestedFilename, PATHINFO_FILENAME)) === ''
            || strtolower((string) pathinfo($requestedFilename, PATHINFO_EXTENSION)) !== 'csv'
        ) {
            throw new InvalidArgumentException(
                'File must be a CSV filename with extension and without a directory path.'
            );
        }

        return $requestedFilename;
    }
}
