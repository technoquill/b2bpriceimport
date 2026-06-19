<?php

declare(strict_types=1);

namespace B2B\PriceImport\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class B2BPriceImportConfig
{
    public const EXCLUDED_GROUPS_FROM_DISCOUNT_MATRIX = 'B2BPRICEIMPORT_EXCLUDED_GROUPS_FROM_DISCOUNT_MATRIX';

    public const TYPE_GROUP_MULTISELECT = 'group_multiselect';

    public const STORAGE_JSON = 'json';

    public const SECTION_DISCOUNT_MATRIX = 'discount_matrix';

    /**
     * Registry всіх налаштувань модуля.
     *
     * Один метод = одне налаштування.
     */
    public function getDefinitions(): array
    {
        return [
            $this->excludedGroupsFromDiscountMatrix(),
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