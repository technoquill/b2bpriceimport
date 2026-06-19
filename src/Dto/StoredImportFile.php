<?php

declare(strict_types=1);

namespace B2B\PriceImport\DTO;

final class StoredImportFile
{
    public function __construct(
        public readonly string $originalFilename,
        public readonly string $storedFilename,
        public readonly string $filePath,
        public readonly int $fileSize,
        public readonly string $fileHash
    ) {
    }
}