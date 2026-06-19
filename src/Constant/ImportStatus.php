<?php

declare(strict_types=1);

namespace B2B\PriceImport\Constant;

final class ImportStatus
{
    public const UPLOADED = 'uploaded';
    public const PARSING = 'parsing';
    public const PARSED = 'parsed';
    public const VALIDATING = 'validating';
    public const VALIDATED = 'validated';
    public const PROCESSING = 'processing';
    public const FINISHED = 'finished';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    public static function activeStatuses(): array
    {
        return [
            self::PARSING,
            self::VALIDATING,
            self::PROCESSING,
        ];
    }
}