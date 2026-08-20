<?php

declare(strict_types=1);

namespace B2B\PriceImport\DTO;

final class ImportRunOptions
{
    public function __construct(
        public readonly ?int $importId,
        public readonly string $type,
        public readonly int $batchLimit,
        public readonly ?int $timeLimit,
        public readonly int $lockTtl,
        public readonly bool $forceLock,
        public readonly string $scanDirectory,
        public readonly int $maxFileAgeHours,
        public readonly int $scanLimit
    ) {
    }
}
