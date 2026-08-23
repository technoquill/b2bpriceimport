<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\DTO\ImportRunOptions;
use B2B\PriceImport\Repository\ImportRepository;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class PriceImportRunService
{
    public const ERROR_LOCKED = 'IMPORT_LOCKED';
    public const ERROR_INVALID_OPTIONS = 'INVALID_OPTIONS';
    public const ERROR_FAILED = 'IMPORT_FAILED';

    private const TYPE_PARSE = 'parse';
    private const TYPE_PROCESS = 'process';
    private const TYPE_ALL = 'all';
    private const RUN_LOCK_NAME = 'b2b_price_import_run';

    public function __construct(
        private readonly ?ImportRepository $repository = null,
        private readonly ?PriceImportParser $parser = null,
        private readonly ?PriceImportProcessor $processor = null,
        private readonly ?ImportLockService $lockService = null,
        private readonly ?ImportFileScannerService $scanner = null,
        private readonly ?AuditLogService $auditLogger = null
    ) {
    }

    public function run(ImportRunOptions $options): array
    {
        $summary = $this->createSummary();
        $lockService = $this->lockService ?: new ImportLockService();
        $auditLogger = $this->auditLogger ?: new AuditLogService();
        $lockAcquired = false;
        $runStarted = false;

        try {
            $requestedFilename = ImportFileScannerService::normalizeRequestedFilename(
                $options->filename
            );
            $this->validateOptions($options);
            $summary['file'] = $requestedFilename;

            if (
                $options->importId !== null
                && $options->importId > 0
                && $requestedFilename !== null
            ) {
                throw new InvalidArgumentException(
                    'File cannot be used together with an import ID.'
                );
            }

            $lockAcquired = $lockService->acquire(
                self::RUN_LOCK_NAME,
                $options->lockTtl,
                $options->forceLock
            );

            if (!$lockAcquired) {
                $summary['error_code'] = self::ERROR_LOCKED;
                $summary['message'] = 'Another price import is already running.';

                $auditLogger->record(
                    'system.import_locked',
                    'system',
                    'warning',
                    $summary['message'],
                    self::RUN_LOCK_NAME,
                    null,
                    null,
                    [
                        'import_id' => $options->importId,
                        'filename' => $requestedFilename,
                    ]
                );

                return $summary;
            }

            $repository = $this->repository ?: new ImportRepository();
            $idImport = $this->resolveImportIdOrScan(
                $options,
                $requestedFilename,
                $repository,
                $summary
            );

            if ($idImport === null) {
                $summary['success'] = true;
                $summary['message'] = 'No eligible CSV file found for import.';

                return $summary;
            }

            $summary['import_id'] = $idImport;
            $summary['type'] = $options->type;
            $startedAt = time();
            $runStarted = true;

            $auditLogger->record(
                'import.started',
                'import',
                'success',
                'Import processing started.',
                (string) $idImport,
                null,
                ['type' => $options->type],
                [
                    'batch_limit' => $options->batchLimit,
                    'time_limit' => $options->timeLimit,
                    'filename' => $requestedFilename,
                ]
            );

            if ($options->type === self::TYPE_PARSE || $options->type === self::TYPE_ALL) {
                $summary['parse'] = ($this->parser ?: new PriceImportParser())->parse($idImport);
            }

            if ($options->type === self::TYPE_PROCESS || $options->type === self::TYPE_ALL) {
                do {
                    $result = ($this->processor ?: new PriceImportProcessor())->process(
                        $idImport,
                        $options->batchLimit
                    );
                    $processed = (int) ($result['processed'] ?? 0);
                    $failed = (int) ($result['failed'] ?? 0);

                    $summary['process']['processed'] += $processed;
                    $summary['process']['failed'] += $failed;
                    $summary['process']['batches']++;

                    $hasMoreWork = ($processed + $failed) >= $options->batchLimit;
                } while (
                    $hasMoreWork
                    && ($options->timeLimit === null || (time() - $startedAt) < $options->timeLimit)
                );
            }

            $summary['success'] = true;
            $summary['message'] = 'Import command finished.';

            $failedRows = (int) ($summary['parse']['failed'] ?? 0)
                + (int) ($summary['process']['failed'] ?? 0);
            $unmatchedRows = (int) ($summary['parse']['warnings'] ?? 0);
            $createdDrafts = (int) ($summary['parse']['created'] ?? 0);
            $auditResult = ($failedRows + $unmatchedRows) > 0 ? 'warning' : 'success';

            if ($failedRows > 0 || $unmatchedRows > 0) {
                $completionMessage = sprintf(
                    'Import completed with %d failed row(s), %d unmatched product(s), and %d draft product(s) automatically created.',
                    $failedRows,
                    $unmatchedRows,
                    $createdDrafts
                );
            } elseif ($createdDrafts > 0) {
                $completionMessage = sprintf(
                    'Import completed successfully; %d draft product(s) automatically created.',
                    $createdDrafts
                );
            } else {
                $completionMessage = 'Import completed successfully.';
            }

            $auditLogger->record(
                'import.completed',
                'import',
                $auditResult,
                $completionMessage,
                (string) $idImport,
                null,
                [
                    'type' => $options->type,
                    'parse' => $summary['parse'],
                    'process' => $summary['process'],
                ],
                ['duration_seconds' => time() - $startedAt]
            );

            if (
                $auditLogger->isSummaryProductLogging()
                && ((int) $summary['process']['processed'] > 0 || (int) $summary['process']['failed'] > 0)
            ) {
                $productAuditResult = (int) $summary['process']['failed'] > 0 ? 'warning' : 'success';
                $auditLogger->record(
                    'product.import_summary',
                    'product',
                    $productAuditResult,
                    sprintf(
                        'Product update summary: %d updated, %d failed.',
                        (int) $summary['process']['processed'],
                        (int) $summary['process']['failed']
                    ),
                    (string) $idImport,
                    null,
                    null,
                    [
                        'import_id' => $idImport,
                        'updated' => (int) $summary['process']['processed'],
                        'failed' => (int) $summary['process']['failed'],
                        'batches' => (int) $summary['process']['batches'],
                    ]
                );
            }
        } catch (InvalidArgumentException $exception) {
            $summary['error_code'] = self::ERROR_INVALID_OPTIONS;
            $summary['message'] = $exception->getMessage();

            $auditLogger->record(
                $runStarted ? 'import.failed' : 'system.import_run_failed',
                $runStarted ? 'import' : 'system',
                'error',
                $exception->getMessage(),
                $runStarted ? (string) ($summary['import_id'] ?? '') : null,
                null,
                null,
                ['error_code' => self::ERROR_INVALID_OPTIONS]
            );
        } catch (Throwable $exception) {
            $summary['error_code'] = self::ERROR_FAILED;
            $summary['message'] = $exception->getMessage();

            $auditLogger->record(
                $runStarted ? 'import.failed' : 'system.import_run_failed',
                $runStarted ? 'import' : 'system',
                'error',
                $exception->getMessage(),
                $runStarted ? (string) ($summary['import_id'] ?? '') : null,
                null,
                null,
                ['error_code' => self::ERROR_FAILED]
            );
        } finally {
            if ($lockAcquired) {
                $lockService->release(self::RUN_LOCK_NAME);
            }
        }

        return $summary;
    }

    private function resolveImportIdOrScan(
        ImportRunOptions $options,
        ?string $requestedFilename,
        ImportRepository $repository,
        array &$summary
    ): ?int {
        if ($options->importId !== null && $options->importId > 0) {
            if ($repository->find($options->importId) === null) {
                throw new RuntimeException('Import not found.');
            }

            return $options->importId;
        }

        $scan = ($this->scanner ?: new ImportFileScannerService($repository))->scanAndCreateImports(
            $options->scanDirectory,
            $options->maxFileAgeHours,
            $options->scanLimit,
            $requestedFilename
        );

        $summary['scan'] = $scan;

        if (empty($scan['created'][0]['id_import'])) {
            return null;
        }

        return (int) $scan['created'][0]['id_import'];
    }

    private function validateOptions(ImportRunOptions $options): void
    {
        if (!in_array($options->type, [self::TYPE_PARSE, self::TYPE_PROCESS, self::TYPE_ALL], true)) {
            throw new InvalidArgumentException('Invalid import type.');
        }

        if ($options->batchLimit < 1 || $options->batchLimit > 5000) {
            throw new InvalidArgumentException('Import batch limit must be between 1 and 5000.');
        }

        if (
            $options->timeLimit !== null
            && ($options->timeLimit < 1 || $options->timeLimit > 3600)
        ) {
            throw new InvalidArgumentException('Import time limit must be between 1 and 3600 seconds.');
        }

        if ($options->lockTtl < 1 || $options->lockTtl > 3600) {
            throw new InvalidArgumentException('Import lock TTL must be between 1 and 3600 seconds.');
        }

        if ($options->maxFileAgeHours < 1 || $options->maxFileAgeHours > 168) {
            throw new InvalidArgumentException('Max file age must be between 1 and 168 hours.');
        }

        if ($options->scanLimit < 1 || $options->scanLimit > 50) {
            throw new InvalidArgumentException('Import scan limit must be between 1 and 50.');
        }

        if (trim($options->scanDirectory) === '') {
            throw new InvalidArgumentException('Import scan directory is required.');
        }
    }

    private function createSummary(): array
    {
        return [
            'success' => false,
            'error_code' => null,
            'import_id' => null,
            'type' => null,
            'file' => null,
            'scan' => null,
            'parse' => null,
            'process' => [
                'processed' => 0,
                'failed' => 0,
                'batches' => 0,
            ],
            'message' => null,
        ];
    }
}
