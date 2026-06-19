<?php

declare(strict_types=1);

namespace B2B\PriceImport\Constant;

final class ImportJobType
{
    public const PARSE = 'parse';
    public const VALIDATE = 'validate';
    public const PROCESS = 'process';
    public const RETRY = 'retry';
    public const CLEANUP = 'cleanup';
}