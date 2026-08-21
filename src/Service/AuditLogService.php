<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Repository\AuditLogRepository;
use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;
use Throwable;

final class AuditLogService
{
    private static bool $retentionChecked = false;
    private AuditLogRepository $repository;
    private B2BPriceImportConfigRepository $configRepository;

    public function __construct(
        ?AuditLogRepository $repository = null,
        ?B2BPriceImportConfigRepository $configRepository = null
    ) {
        $this->repository = $repository ?: new AuditLogRepository();
        $this->configRepository = $configRepository ?: new B2BPriceImportConfigRepository();
    }

    public function record(
        string $action,
        string $entityType,
        string $result,
        string $message,
        ?string $entityId = null,
        ?array $before = null,
        ?array $after = null,
        array $context = [],
        ?string $channel = null
    ): bool {
        try {
            $config = $this->configRepository;

            if (
                !$config->isLoggingEnabled()
                || !$config->shouldLogEntityType($entityType)
                || !$config->shouldLogResult($result)
            ) {
                return false;
            }

            $repository = $this->repository;
            $this->purgeExpiredOnce($repository, $config);
            $actor = $this->resolveActor($channel);
            $storeChanges = $config->shouldStoreLogChanges();

            $repository->create([
                'action' => trim($action),
                'entity_type' => trim($entityType),
                'entity_id' => $entityId,
                'result' => trim($result),
                'actor_type' => $actor['type'],
                'actor_id' => $actor['id'],
                'actor_name' => $actor['name'],
                'channel' => $actor['channel'],
                'message' => trim($message),
                'before' => $storeChanges ? $this->redact($before) : null,
                'after' => $storeChanges ? $this->redact($after) : null,
                'context' => $this->redact($context),
            ]);

            return true;
        } catch (Throwable) {
            // Audit logging must never interrupt the action being recorded.
            return false;
        }
    }

    public function purgeExpired(): int
    {
        try {
            $config = $this->configRepository;

            return $this->repository->deleteOlderThan($config->getLogRetentionDays());
        } catch (Throwable) {
            return 0;
        }
    }

    public function isDetailedProductLogging(): bool
    {
        try {
            $config = $this->configRepository;

            return $config->isLoggingEnabled()
                && $config->shouldLogEntityType('product')
                && $config->getLogProductMode() === 'detailed';
        } catch (Throwable) {
            return false;
        }
    }

    public function isSummaryProductLogging(): bool
    {
        try {
            $config = $this->configRepository;

            return $config->isLoggingEnabled()
                && $config->shouldLogEntityType('product')
                && $config->getLogProductMode() === 'summary';
        } catch (Throwable) {
            return false;
        }
    }

    private function purgeExpiredOnce(
        AuditLogRepository $repository,
        B2BPriceImportConfigRepository $config
    ): void {
        if (self::$retentionChecked) {
            return;
        }

        self::$retentionChecked = true;
        $repository->deleteOlderThan($config->getLogRetentionDays());
    }

    private function resolveActor(?string $requestedChannel): array
    {
        $channel = trim((string) $requestedChannel);

        if ($channel === '') {
            $channel = PHP_SAPI === 'cli' ? 'cli' : 'api';
        }

        $employeeId = null;
        $employeeName = null;

        if (class_exists('Context')) {
            try {
                $context = \Context::getContext();
                $employee = $context->employee ?? null;
                $candidateId = isset($employee->id) ? (int) $employee->id : 0;

                if ($candidateId > 0) {
                    $employeeId = $candidateId;
                    $employeeName = trim(
                        (string) ($employee->firstname ?? '') . ' ' . (string) ($employee->lastname ?? '')
                    );
                    $channel = $requestedChannel !== null ? $channel : 'admin';
                }
            } catch (Throwable) {
            }
        }

        if ($employeeId !== null) {
            return [
                'type' => 'employee',
                'id' => $employeeId,
                'name' => $employeeName !== '' ? $employeeName : 'Employee #' . $employeeId,
                'channel' => $channel,
            ];
        }

        if ($channel === 'cli') {
            return ['type' => 'cli', 'id' => null, 'name' => 'CLI', 'channel' => 'cli'];
        }

        if ($channel === 'scanner' || $channel === 'system') {
            return ['type' => 'system', 'id' => null, 'name' => 'System', 'channel' => $channel];
        }

        return ['type' => 'api', 'id' => null, 'name' => 'API', 'channel' => $channel ?: 'api'];
    }

    private function redact(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as $key => &$value) {
            $keyString = (string) $key;

            if (preg_match('/password|secret|token|authorization|api[_-]?key|access[_-]?key/i', $keyString)) {
                $value = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $value = $this->redact($value);
            }
        }
        unset($value);

        return $data;
    }
}
