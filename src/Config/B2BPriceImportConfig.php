<?php

declare(strict_types=1);

namespace B2B\PriceImport\Config;

use B2B\PriceImport\Service\ImportFileStorageService;

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
    public const IMPORT_API_KEY = 'B2BPRICEIMPORT_IMPORT_API_KEY';
    public const LOG_ENABLED = 'B2BPRICEIMPORT_LOG_ENABLED';
    public const LOG_ENTITY_TYPES = 'B2BPRICEIMPORT_LOG_ENTITY_TYPES';
    public const LOG_RESULTS = 'B2BPRICEIMPORT_LOG_RESULTS';
    public const LOG_STORE_CHANGES = 'B2BPRICEIMPORT_LOG_STORE_CHANGES';
    public const LOG_PRODUCT_MODE = 'B2BPRICEIMPORT_LOG_PRODUCT_MODE';
    public const LOG_RETENTION_DAYS = 'B2BPRICEIMPORT_LOG_RETENTION_DAYS';

    public const TYPE_GROUP_MULTISELECT = 'group_multiselect';
    public const TYPE_MULTISELECT = 'multiselect';
    public const TYPE_TEXT = 'text';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_SELECT = 'select';
    public const TYPE_SECRET = 'secret';

    public const STORAGE_JSON = 'json';
    public const STORAGE_SCALAR = 'scalar';

    public const SECTION_DISCOUNT_MATRIX = 'discount_matrix';
    public const SECTION_IMPORT = 'import';
    public const SECTION_LOGGING = 'logging';

    public const GROUP_1C_INTEGRATION = '1c_integration';
    public const GROUP_IMPORT_PROCESSING = 'import_processing';
    public const GROUP_ADVANCED_CLI = 'advanced_cli';
    public const GROUP_SYSTEM_INFORMATION = 'system_information';
    public const GROUP_DISCOUNT_MATRIX = 'discount_matrix';
    public const GROUP_LOGGING = 'logging';

    public const LOG_ENTITY_FILE = 'file';
    public const LOG_ENTITY_IMPORT = 'import';
    public const LOG_ENTITY_PRODUCT = 'product';
    public const LOG_ENTITY_CONFIG = 'config';
    public const LOG_ENTITY_DISCOUNT_RULE = 'discount_rule';
    public const LOG_ENTITY_SYSTEM = 'system';

    public const LOG_RESULT_SUCCESS = 'success';
    public const LOG_RESULT_WARNING = 'warning';
    public const LOG_RESULT_ERROR = 'error';

    public const LOG_PRODUCT_MODE_SUMMARY = 'summary';
    public const LOG_PRODUCT_MODE_DETAILED = 'detailed';

    public function getGroups(): array
    {
        return [
            [
                'key' => self::GROUP_1C_INTEGRATION,
                'section' => self::SECTION_IMPORT,
                'label' => '1C integration',
                'description' => 'Access settings used by 1C to start the import.',
                'icon' => 'icon-exchange',
                'collapsed' => false,
                'show_import_trigger_url' => true,
                'order' => 10,
            ],
            [
                'key' => self::GROUP_IMPORT_PROCESSING,
                'section' => self::SECTION_IMPORT,
                'label' => 'Import processing',
                'description' => 'Controls how many import rows are processed in one batch.',
                'icon' => 'icon-tasks',
                'collapsed' => false,
                'order' => 20,
            ],
            [
                'key' => self::GROUP_ADVANCED_CLI,
                'section' => self::SECTION_IMPORT,
                'label' => 'Advanced CLI settings',
                'description' => 'File scanner, runtime, locking, and terminal output defaults.',
                'icon' => 'icon-terminal',
                'collapsed' => false,
                'order' => 30,
            ],
            [
                'key' => self::GROUP_SYSTEM_INFORMATION,
                'section' => self::SECTION_IMPORT,
                'label' => 'System information',
                'description' => 'Read-only paths and the ready-to-copy terminal command.',
                'icon' => 'icon-info-circle',
                'collapsed' => false,
                'show_cli_command' => true,
                'order' => 40,
            ],
            [
                'key' => self::GROUP_DISCOUNT_MATRIX,
                'section' => self::SECTION_DISCOUNT_MATRIX,
                'label' => 'Discount Matrix display',
                'description' => 'Choose which customer groups are visible in the matrix.',
                'icon' => 'icon-users',
                'collapsed' => false,
                'order' => 50,
            ],
            [
                'key' => self::GROUP_LOGGING,
                'section' => self::SECTION_LOGGING,
                'label' => 'Logging',
                'description' => 'Choose which module actions are written to the audit log and how long they are retained.',
                'icon' => 'icon-list-alt',
                'collapsed' => false,
                'order' => 60,
            ],
        ];
    }

    public function getDefinitions(): array
    {
        return [
            $this->excludedGroupsFromDiscountMatrix(),
            $this->importApiKey(),
            $this->importScanDir(),
            $this->importMaxFileAgeHours(),
            $this->importScanLimit(),
            $this->importRunType(),
            $this->importBatchLimit(),
            $this->importTimeLimit(),
            $this->importLockTtl(),
            $this->importOutputFormat(),
            $this->logEnabled(),
            $this->logEntityTypes(),
            $this->logResults(),
            $this->logStoreChanges(),
            $this->logProductMode(),
            $this->logRetentionDays(),
        ];
    }

    public function logEnabled(): array
    {
        return [
            'key' => self::LOG_ENABLED,
            'section' => self::SECTION_LOGGING,
            'group' => self::GROUP_LOGGING,
            'type' => self::TYPE_SELECT,
            'storage' => self::STORAGE_SCALAR,
            'default' => '1',
            'label' => 'Enable logging',
            'description' => 'Master switch for writing module actions to the audit log.',
            'options' => [
                ['value' => '1', 'label' => 'Yes'],
                ['value' => '0', 'label' => 'No'],
            ],
        ];
    }

    public function logEntityTypes(): array
    {
        return [
            'key' => self::LOG_ENTITY_TYPES,
            'section' => self::SECTION_LOGGING,
            'group' => self::GROUP_LOGGING,
            'type' => self::TYPE_MULTISELECT,
            'storage' => self::STORAGE_JSON,
            'default' => [
                self::LOG_ENTITY_FILE,
                self::LOG_ENTITY_IMPORT,
                self::LOG_ENTITY_PRODUCT,
                self::LOG_ENTITY_CONFIG,
                self::LOG_ENTITY_DISCOUNT_RULE,
                self::LOG_ENTITY_SYSTEM,
            ],
            'label' => 'Entity types to log',
            'description' => 'Only actions for the selected entity types will be written to the audit log.',
            'options' => [
                ['value' => self::LOG_ENTITY_FILE, 'label' => 'Files'],
                ['value' => self::LOG_ENTITY_IMPORT, 'label' => 'Imports'],
                ['value' => self::LOG_ENTITY_PRODUCT, 'label' => 'Products'],
                ['value' => self::LOG_ENTITY_CONFIG, 'label' => 'Configuration'],
                ['value' => self::LOG_ENTITY_DISCOUNT_RULE, 'label' => 'Discount rules'],
                ['value' => self::LOG_ENTITY_SYSTEM, 'label' => 'System'],
            ],
        ];
    }

    public function logResults(): array
    {
        return [
            'key' => self::LOG_RESULTS,
            'section' => self::SECTION_LOGGING,
            'group' => self::GROUP_LOGGING,
            'type' => self::TYPE_MULTISELECT,
            'storage' => self::STORAGE_JSON,
            'default' => [
                self::LOG_RESULT_SUCCESS,
                self::LOG_RESULT_WARNING,
                self::LOG_RESULT_ERROR,
            ],
            'label' => 'Results to log',
            'description' => 'Record successful actions, warnings, errors, or any combination of them.',
            'options' => [
                ['value' => self::LOG_RESULT_SUCCESS, 'label' => 'Success'],
                ['value' => self::LOG_RESULT_WARNING, 'label' => 'Warning'],
                ['value' => self::LOG_RESULT_ERROR, 'label' => 'Error'],
            ],
        ];
    }

    public function logStoreChanges(): array
    {
        return [
            'key' => self::LOG_STORE_CHANGES,
            'section' => self::SECTION_LOGGING,
            'group' => self::GROUP_LOGGING,
            'type' => self::TYPE_SELECT,
            'storage' => self::STORAGE_SCALAR,
            'default' => '1',
            'label' => 'Store before/after values',
            'description' => 'Store previous and new values when an action changes data. Secrets are always excluded.',
            'options' => [
                ['value' => '1', 'label' => 'Yes'],
                ['value' => '0', 'label' => 'No'],
            ],
        ];
    }

    public function logProductMode(): array
    {
        return [
            'key' => self::LOG_PRODUCT_MODE,
            'section' => self::SECTION_LOGGING,
            'group' => self::GROUP_LOGGING,
            'type' => self::TYPE_SELECT,
            'storage' => self::STORAGE_SCALAR,
            'default' => self::LOG_PRODUCT_MODE_SUMMARY,
            'label' => 'Product logging mode',
            'description' => 'Summary writes aggregate product changes per import; detailed writes one event for every changed product.',
            'options' => [
                ['value' => self::LOG_PRODUCT_MODE_SUMMARY, 'label' => 'Summary per import'],
                ['value' => self::LOG_PRODUCT_MODE_DETAILED, 'label' => 'Every product change'],
            ],
        ];
    }

    public function logRetentionDays(): array
    {
        return [
            'key' => self::LOG_RETENTION_DAYS,
            'section' => self::SECTION_LOGGING,
            'group' => self::GROUP_LOGGING,
            'type' => self::TYPE_INTEGER,
            'storage' => self::STORAGE_SCALAR,
            'default' => 180,
            'min' => 0,
            'max' => 3650,
            'label' => 'Log retention, days',
            'description' => 'Delete log entries older than this value. Use 0 to keep logs indefinitely.',
        ];
    }

    public function importApiKey(): array
    {
        return [
            'key' => self::IMPORT_API_KEY,
            'section' => self::SECTION_IMPORT,
            'group' => self::GROUP_1C_INTEGRATION,
            'type' => self::TYPE_SECRET,
            'storage' => self::STORAGE_SCALAR,
            'default' => '',
            'min_length' => 32,
            'max_length' => 255,
            'label' => 'Import URL access key',
            'description' => 'Secret key required to start an import through the URL. Enter a key or generate a secure one.',
        ];
    }

    public function excludedGroupsFromDiscountMatrix(): array
    {
        return [
            'key' => self::EXCLUDED_GROUPS_FROM_DISCOUNT_MATRIX,
            'section' => self::SECTION_DISCOUNT_MATRIX,
            'group' => self::GROUP_DISCOUNT_MATRIX,
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
            'group' => self::GROUP_SYSTEM_INFORMATION,
            'type' => self::TYPE_TEXT,
            'storage' => self::STORAGE_SCALAR,
            'default' => ImportFileStorageService::getDefaultDirectory(),
            'persisted' => false,
            'readonly' => true,
            'copyable' => true,
            'label' => 'Import scan directory',
            'description' => 'Read-only directory where the CLI command scans for fresh CSV files when --import-id is omitted.',
        ];
    }

    public function importMaxFileAgeHours(): array
    {
        return [
            'key' => self::IMPORT_MAX_FILE_AGE_HOURS,
            'section' => self::SECTION_IMPORT,
            'group' => self::GROUP_ADVANCED_CLI,
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
            'group' => self::GROUP_ADVANCED_CLI,
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
            'group' => self::GROUP_ADVANCED_CLI,
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
            'group' => self::GROUP_IMPORT_PROCESSING,
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
            'group' => self::GROUP_ADVANCED_CLI,
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
            'group' => self::GROUP_ADVANCED_CLI,
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
            'group' => self::GROUP_ADVANCED_CLI,
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
