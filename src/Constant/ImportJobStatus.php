<?php

declare(strict_types=1);

namespace B2B\PriceImport\Constant;

final class ImportJobStatus
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const FINISHED = 'finished';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
}