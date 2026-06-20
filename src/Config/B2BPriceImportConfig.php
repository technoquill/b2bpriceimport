<?php

declare(strict_types=1);

namespace B2B\PriceImport\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class B2BPriceImportConfig
{
    public const EXCLUDED_GROUPS_FROM_DISCOUNT_MATRIX = 'B2BPRICEIMPORT_EXCLUDED_GROUPS_FROM_DISCOUNT_MATRIX';
    public const IMPORT_SCAN_DIR = 'B2BPRICEIMPORT_IMPORT_SCAN_DIR';
    public const IMPORT_MAX_FILE_AGE_HOURS = 'B2BPRICEIMPORT_IMPORT_MAX_FILE_AGE_HOURS';
    public const IMPORT_SCAN_LIMIT = 'B2BPRICEIMPORT_IMPORT_SCAN_LIMIT';
    public const IMPORT_RUN_TYPE = 'B2BPRICEIMPORT_IMPORT_RUN_TYPE';
    public const IMPORT_BATCH_LIMIT = 'B2BPRICEIMPORT_IMPORT_BATCH_LIMIT';
    public const IMPORT_TIME_LIMIT = 'B2BPRICEIMPORT_IMPORT_TIME_LIMIT';
    public const IMPORT_LOCK_TTL = 'B2BPRICEIMPORT_IMPORT_LOCK_TTL';
    public const IMPORT_OUTPUT_FORMAT = 'B2BPRICEIMPORT_IMPORT_OUTPUT_FORMAT';

    public const TYPE_GROUP_MULTISELECT = 'group_multiselect';
    public const TYPE_TEXT = 'text';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_SELECT = 'select';

    public const STORAGE_JSON = 'json';
    public const STORAGE_SCALAR = 'scalar';

    public const SECTION_DISCOUNT_MATRIX = 'discount_matrix';
    public const SECTION_IMPORT = 'import';

    /**
     * Registry всіх налаштувань модуля.
     *
     * Один метод = одне налаштування.
     */
    public function getDefinitions(): array
    {
        return [
            $this->excludedGroupsFromDiscountMatrix(),
            $this->importScanDir(),
            $this->importMaxFileAgeHours(),
            $this->importScanLimit(),
            $this->importRunType(),
            $this->importBatchLimit(),
            $this->importTimeLimit(),
            $this->importLockTtl(),
            $this->importOutputFormat(),
        ];
    }

    /**
     * Налаштування:
     * які групи клієнтів не показувати в B2B Discount Matrix.
     */
    public function excludedGroupsFromDiscountMatrix(): array
    {
        return [
            'key' => self::EXCLUDED_GROUPS_FROM_DISCOUNT_MATRIX,
            'section' => self::SECTION_DISCOUNT_MATRIX,
            'type' => self::TYPE_GROUP_MULTISELECT,
            'storage' => self::STORAGE_JSON,
            'default' => [],
            'label' => 'Exclude groups from B2B Discount Matrix',
            'description' => 'Selected customer groups will not be shown as columns in the discount matrix.',
            'options_provider' => 'customer_groups',
        ];
    }

    public function importScanDir(): array
    {
        return [
            'key' => self::IMPORT_SCAN_DIR,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_TEXT,
            'storage' => self::STORAGE_SCALAR,
            'default' => _PS_MODULE_DIR_ . 'b2bpriceimport/var/imports/inbox',
            'label' => 'Import scan directory',
            'description' => 'Directory where the CLI command scans for fresh CSV files when --import-id is omitted.',
        ];
    }

    public function importMaxFileAgeHours(): array
    {
        return [
            'key' => self::IMPORT_MAX_FILE_AGE_HOURS,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_INTEGER,
            'storage' => self::STORAGE_SCALAR,
            'default' => 24,
            'min' => 1,
            'max' => 168,
            'label' => 'Max file age, hours',
            'description' => 'CSV files older than this value will not be registered by the CLI scanner.',
        ];
    }

    public function importScanLimit(): array
    {
        return [
            'key' => self::IMPORT_SCAN_LIMIT,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_INTEGER,
            'storage' => self::STORAGE_SCALAR,
            'default' => 1,
            'min' => 1,
            'max' => 50,
            'label' => 'Scan limit',
            'description' => 'Maximum number of new CSV files registered by one CLI run.',
        ];
    }

    public function importRunType(): array
    {
        return [
            'key' => self::IMPORT_RUN_TYPE,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_SELECT,
            'storage' => self::STORAGE_SCALAR,
            'default' => 'all',
            'label' => 'Default import run type',
            'description' => 'Default stage used by the CLI command when --type is omitted.',
            'options' => [
                ['value' => 'parse', 'label' => 'Parse only'],
                ['value' => 'process', 'label' => 'Process only'],
                ['value' => 'all', 'label' => 'Parse and process'],
            ],
        ];
    }

    public function importBatchLimit(): array
    {
        return [
            'key' => self::IMPORT_BATCH_LIMIT,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_INTEGER,
            'storage' => self::STORAGE_SCALAR,
            'default' => 500,
            'min' => 1,
            'max' => 5000,
            'label' => 'Import batch limit',
            'description' => 'Default row limit for one CLI processing batch when --limit is omitted.',
        ];
    }

    public function importTimeLimit(): array
    {
        return [
            'key' => self::IMPORT_TIME_LIMIT,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_INTEGER,
            'storage' => self::STORAGE_SCALAR,
            'default' => 55,
            'min' => 1,
            'max' => 3600,
            'label' => 'CLI time limit, seconds',
            'description' => 'Default maximum runtime for one CLI command when --time-limit is omitted.',
        ];
    }

    public function importLockTtl(): array
    {
        return [
            'key' => self::IMPORT_LOCK_TTL,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_INTEGER,
            'storage' => self::STORAGE_SCALAR,
            'default' => 120,
            'min' => 1,
            'max' => 3600,
            'label' => 'CLI lock TTL, seconds',
            'description' => 'Default MySQL import lock TTL when --lock-ttl is omitted.',
        ];
    }

    public function importOutputFormat(): array
    {
        return [
            'key' => self::IMPORT_OUTPUT_FORMAT,
            'section' => self::SECTION_IMPORT,
            'type' => self::TYPE_SELECT,
            'storage' => self::STORAGE_SCALAR,
            'default' => 'text',
            'label' => 'CLI output format',
            'description' => 'Default output format when --format is omitted.',
            'options' => [
                ['value' => 'text', 'label' => 'Text'],
                ['value' => 'json', 'label' => 'JSON'],
            ],
        ];
    }

    public function getDefinitionByKey(string $key): ?array
    {
        foreach ($this->getDefinitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }

    public function hasDefinition(string $key): bool
    {
        return $this->getDefinitionByKey($key) !== null;
    }
}
