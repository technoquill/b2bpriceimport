<?php

declare(strict_types=1);

namespace B2B\PriceImport\Repository;

use B2B\PriceImport\Config\B2BPriceImportConfig;
use Configuration;
use Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class B2BPriceImportConfigRepository
{
    private B2BPriceImportConfig $config;

    public function __construct(?B2BPriceImportConfig $config = null)
    {
        $this->config = $config ?: new B2BPriceImportConfig();
    }

    public function getDefinitions(): array
    {
        $definitions = [];

        foreach ($this->config->getDefinitions() as $definition) {
            $definition['value'] = $this->get($definition['key']);
            $definitions[] = $definition;
        }

        return $definitions;
    }

    /**
     * @throws Exception
     */
    public function get(string $key)
    {
        $definition = $this->config->getDefinitionByKey($key);

        if ($definition === null) {
            throw new Exception('Unknown configuration key: ' . $key);
        }

        $rawValue = Configuration::get($key);

        if ($rawValue === false || $rawValue === null || $rawValue === '') {
            return $definition['default'];
        }

        return $this->normalizeFromStorage($definition, $rawValue);
    }

    /**
     * @throws Exception
     */
    public function save(string $key, $value)
    {
        $definition = $this->config->getDefinitionByKey($key);

        if ($definition === null) {
            throw new Exception('Unknown configuration key: ' . $key);
        }

        $normalizedValue = $this->normalizeForStorage($definition, $value);
        $storageValue = $this->serializeForStorage($definition, $normalizedValue);

        Configuration::updateValue($key, $storageValue);

        return $normalizedValue;
    }

    /**
     * @throws Exception
     */
    public function getExcludedGroupsFromDiscountMatrix(): array
    {
        return $this->get(B2BPriceImportConfig::EXCLUDED_GROUPS_FROM_DISCOUNT_MATRIX);
    }

    /**
     * @throws Exception
     */
    public function isGroupExcludedFromDiscountMatrix(int $idGroup): bool
    {
        return in_array($idGroup, $this->getExcludedGroupsFromDiscountMatrix(), true);
    }

    /**
     * @throws Exception
     */
    public function getImportScanDir(): string
    {
        return (string) $this->get(B2BPriceImportConfig::IMPORT_SCAN_DIR);
    }

    /**
     * @throws Exception
     */
    public function getImportMaxFileAgeHours(): int
    {
        return (int) $this->get(B2BPriceImportConfig::IMPORT_MAX_FILE_AGE_HOURS);
    }

    /**
     * @throws Exception
     */
    public function getImportScanLimit(): int
    {
        return (int) $this->get(B2BPriceImportConfig::IMPORT_SCAN_LIMIT);
    }

    /**
     * @throws Exception
     */
    public function getImportRunType(): string
    {
        return (string) $this->get(B2BPriceImportConfig::IMPORT_RUN_TYPE);
    }

    /**
     * @throws Exception
     */
    public function getImportBatchLimit(): int
    {
        return (int) $this->get(B2BPriceImportConfig::IMPORT_BATCH_LIMIT);
    }

    /**
     * @throws Exception
     */
    public function getImportTimeLimit(): int
    {
        return (int) $this->get(B2BPriceImportConfig::IMPORT_TIME_LIMIT);
    }

    /**
     * @throws Exception
     */
    public function getImportLockTtl(): int
    {
        return (int) $this->get(B2BPriceImportConfig::IMPORT_LOCK_TTL);
    }

    /**
     * @throws Exception
     */
    public function getImportOutputFormat(): string
    {
        return (string) $this->get(B2BPriceImportConfig::IMPORT_OUTPUT_FORMAT);
    }

    /**
     * @throws Exception
     */
    private function normalizeFromStorage(array $definition, $rawValue)
    {
        $storage = $definition['storage'] ?? null;

        if ($storage === B2BPriceImportConfig::STORAGE_JSON) {
            $decodedValue = json_decode((string) $rawValue, true);

            if (!is_array($decodedValue)) {
                return $definition['default'];
            }

            return $this->normalizeByType($definition, $decodedValue);
        }

        return $this->normalizeByType($definition, $rawValue);
    }

    /**
     * @throws Exception
     */
    private function normalizeForStorage(array $definition, $value)
    {
        return $this->normalizeByType($definition, $value);
    }

    /**
     * @throws Exception
     */
    private function normalizeByType(array $definition, $value)
    {
        $type = $definition['type'] ?? null;

        if ($type === B2BPriceImportConfig::TYPE_GROUP_MULTISELECT) {
            if (!is_array($value)) {
                $value = [];
            }

            $value = array_map('intval', $value);
            $value = array_filter($value, static function (int $id): bool {
                return $id > 0;
            });

            return array_values(array_unique($value));
        }

        if ($type === B2BPriceImportConfig::TYPE_TEXT) {
            return trim((string) $value);
        }

        if ($type === B2BPriceImportConfig::TYPE_INTEGER) {
            $value = (int) $value;
            $min = isset($definition['min']) ? (int) $definition['min'] : null;
            $max = isset($definition['max']) ? (int) $definition['max'] : null;

            if ($min !== null && $value < $min) {
                throw new Exception('Configuration value is below the allowed minimum.');
            }

            if ($max !== null && $value > $max) {
                throw new Exception('Configuration value is above the allowed maximum.');
            }

            return $value;
        }

        if ($type === B2BPriceImportConfig::TYPE_SELECT) {
            $value = (string) $value;
            $allowedValues = [];

            foreach (($definition['options'] ?? []) as $option) {
                $allowedValues[] = (string) $option['value'];
            }

            if (!in_array($value, $allowedValues, true)) {
                throw new Exception('Configuration value is not allowed.');
            }

            return $value;
        }

        throw new Exception('Unsupported configuration type.');
    }

    /**
     * @throws Exception
     */
    private function serializeForStorage(array $definition, $value): string
    {
        $storage = $definition['storage'] ?? null;

        if ($storage === B2BPriceImportConfig::STORAGE_JSON) {
            return json_encode($value);
        }

        if (is_array($value)) {
            throw new Exception('Array value requires JSON storage.');
        }

        return (string) $value;
    }
}
