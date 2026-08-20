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
        private readonly ?ImportFileScannerService $scanner = null
    ) {
    }

    public function run(ImportRunOptions $options): array
    {
        $summary = $this->createSummary();
        $lockService = $this->lockService ?: new ImportLockService();
        $lockAcquired = false;

        try {
            $this->validateOptions($options);

            $lockAcquired = $lockService->acquire(
                self::RUN_LOCK_NAME,
                $options->lockTtl,
                $options->forceLock
            );

            if (!$lockAcquired) {
                $summary['error_code'] = self::ERROR_LOCKED;
                $summary['message'] = 'Another price import is already running.';

                return $summary;
            }

            $repository = $this->repository ?: new ImportRepository();
            $idImport = $this->resolveImportIdOrScan($options, $repository, $summary);

            if ($idImport === null) {
                $summary['success'] = true;
                $summary['message'] = 'No eligible CSV file found for import.';

                return $summary;
            }

            $summary['import_id'] = $idImport;
            $summary['type'] = $options->type;
            $startedAt = time();

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
        } catch (Throwable $exception) {
            $summary['error_code'] = self::ERROR_FAILED;
            $summary['message'] = $exception->getMessage();
        } finally {
            if ($lockAcquired) {
                $lockService->release(self::RUN_LOCK_NAME);
            }
        }

        return $summary;
    }

    private function resolveImportIdOrScan(
        ImportRunOptions $options,
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
            $options->scanLimit
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
