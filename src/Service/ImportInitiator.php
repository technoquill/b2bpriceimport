<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use B2B\PriceImport\Constant\ImportJobType;
use B2B\PriceImport\DTO\ImportCreateData;
use B2B\PriceImport\Repository\ImportJobRepository;
use B2B\PriceImport\Repository\ImportRepository;

final class ImportInitiator
{
    public function __construct(
        private readonly ImportRepository $importRepository,
        private readonly ImportJobRepository $jobRepository
    ) {
    }

    public function initiate(ImportCreateData $data): int
    {
        if ($this->importRepository->hasActiveImport()) {
            throw new \RuntimeException('Another import is already active.');
        }

        $importId = $this->importRepository->create($data);

        $this->jobRepository->create(
            importId: $importId,
            type: ImportJobType::PARSE,
            priority: 5
        );

        return $importId;
    }
}