<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(_PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php')) {
    require_once _PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php';
}

use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;
use B2B\PriceImport\Repository\ImportRepository;
use B2B\PriceImport\Service\ImportFileStorageService;
use B2B\PriceImport\Service\PriceImportParser;
use B2B\PriceImport\Service\PriceImportProcessor;

class AdminB2BPriceImportController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $activeSection = $this->getActiveSection();

        $assign = [
            'activeSection' => $activeSection,
            'menuItems' => $this->getMenuItems(),
            'ajaxUrl' => $this->context->link->getAdminLink('AdminB2BPriceImport', true),
        ];

        if ($activeSection === 'config') {
            $configRepository = $this->getConfigRepository();
            $assign['configDefinitions'] = $configRepository->getDefinitions();
            $assign['allGroups'] = $this->getAllCustomerGroups();
        }

        if ($activeSection === 'discount_matrix') {
            $assign['matrix'] = $this->buildMatrix();
            $assign['groups'] = $this->getCustomerGroups();
        }

        if ($activeSection === 'import') {
            $assign['imports'] = $this->getImportRepository()->getLastImports(20);
        }

        $this->context->smarty->assign($assign);

        $this->setTemplate('index.tpl');
    }

    private function getActiveSection(): string
    {
        $section = (string) Tools::getValue('section', 'config');

        $allowedSections = [
            'config',
            'discount_matrix',
            'import',
            'logs',
        ];

        if (!in_array($section, $allowedSections, true)) {
            return 'config';
        }

        return $section;
    }

    private function getMenuItems(): array
    {
        $baseUrl = $this->context->link->getAdminLink('AdminB2BPriceImport', true);

        return [
            [
                'key' => 'discount_matrix',
                'label' => $this->l('Discount Matrix'),
                'icon' => 'icon-percent',
                'url' => $baseUrl . '&section=discount_matrix',
            ],
            [
                'key' => 'config',
                'label' => $this->l('Configuration'),
                'icon' => 'icon-cog',
                'url' => $baseUrl . '&section=config',
            ],
            [
                'key' => 'import',
                'label' => $this->l('Import'),
                'icon' => 'icon-upload',
                'url' => $baseUrl . '&section=import',
            ],
            [
                'key' => 'logs',
                'label' => $this->l('Logs'),
                'icon' => 'icon-list',
                'url' => $baseUrl . '&section=logs',
            ],
        ];
    }

    public function ajaxProcessSaveConfig()
    {
        header('Content-Type: application/json');

        $key = (string) Tools::getValue('key');
        $value = Tools::getValue('value', []);

        try {
            $savedValue = $this->getConfigRepository()->save($key, $value);

            die(json_encode([
                'success' => true,
                'message' => 'Configuration saved.',
                'value' => $savedValue,
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessCreateImport()
    {
        header('Content-Type: application/json');

        try {
            if (empty($_FILES['import_file'])) {
                throw new Exception('Import file is required.');
            }

            $employeeId = isset($this->context->employee->id) ? (int) $this->context->employee->id : null;
            $createData = (new ImportFileStorageService())->storeUploadedCsv($_FILES['import_file'], $employeeId);

            $repository = $this->getImportRepository();
            $idImport = $repository->create($createData);
            $repository->createJob($idImport, 'parse');
            $repository->createJob($idImport, 'process');

            die(json_encode([
                'success' => true,
                'message' => 'Import created.',
                'id_import' => $idImport,
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessRunImport()
    {
        header('Content-Type: application/json');

        $idImport = (int) Tools::getValue('id_import');

        try {
            if ($idImport <= 0) {
                throw new Exception('Invalid import id.');
            }

            $parseResult = (new PriceImportParser())->parse($idImport);
            $processResult = (new PriceImportProcessor())->process($idImport);

            die(json_encode([
                'success' => true,
                'message' => 'Import processed.',
                'parse' => $parseResult,
                'process' => $processResult,
            ]));
        } catch (Exception $e) {
            if ($idImport > 0) {
                $this->getImportRepository()->setStatus($idImport, 'failed', $e->getMessage());
            }

            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessSaveDiscountRule()
    {
        header('Content-Type: application/json');

        $idCategory = (int) Tools::getValue('id_category');
        $idManufacturer = (int) Tools::getValue('id_manufacturer');
        $idGroup = (int) Tools::getValue('id_group');
        $rawValue = trim((string) Tools::getValue('discount_percent'));

        if ($idCategory <= 0 || $idManufacturer <= 0 || $idGroup <= 0) {
            die(json_encode([
                'success' => false,
                'message' => 'Invalid category, brand or group.',
            ]));
        }

        try {
            if ($rawValue === '') {
                Db::getInstance()->delete(
                    'b2b_discount_rule',
                    'id_category = ' . (int) $idCategory .
                    ' AND id_manufacturer = ' . (int) $idManufacturer .
                    ' AND id_group = ' . (int) $idGroup
                );

                die(json_encode([
                    'success' => true,
                    'message' => 'Rule removed.',
                    'value' => '',
                ]));
            }

            $discountPercent = (float) str_replace(',', '.', $rawValue);

            if ($discountPercent < 0 || $discountPercent > 100) {
                die(json_encode([
                    'success' => false,
                    'message' => 'Discount must be between 0 and 100.',
                ]));
            }

            $existingId = (int) Db::getInstance()->getValue(
                'SELECT id_b2b_discount_rule
                 FROM `' . _DB_PREFIX_ . 'b2b_discount_rule`
                 WHERE id_category = ' . (int) $idCategory . '
                   AND id_manufacturer = ' . (int) $idManufacturer . '
                   AND id_group = ' . (int) $idGroup
            );

            $data = [
                'id_category' => $idCategory,
                'id_manufacturer' => $idManufacturer,
                'id_group' => $idGroup,
                'discount_percent' => $discountPercent,
                'active' => 1,
                'date_upd' => date('Y-m-d H:i:s'),
            ];

            if ($existingId > 0) {
                Db::getInstance()->update(
                    'b2b_discount_rule',
                    $data,
                    'id_b2b_discount_rule = ' . (int) $existingId
                );
            } else {
                $data['date_add'] = date('Y-m-d H:i:s');

                Db::getInstance()->insert('b2b_discount_rule', $data);
            }

            die(json_encode([
                'success' => true,
                'message' => 'Saved.',
                'value' => number_format($discountPercent, 2, '.', ''),
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    private function getConfigRepository(): B2BPriceImportConfigRepository
    {
        return new B2BPriceImportConfigRepository();
    }

    private function getImportRepository(): ImportRepository
    {
        return new ImportRepository();
    }

    private function getCustomerGroups(): array
    {
        $groups = $this->getAllCustomerGroups();
        $excludedGroupIds = $this->getConfigRepository()->getExcludedGroupsFromDiscountMatrix();

        if (empty($excludedGroupIds)) {
            return $groups;
        }

        $filteredGroups = [];

        foreach ($groups as $group) {
            if (!in_array((int) $group['id_group'], $excludedGroupIds, true)) {
                $filteredGroups[] = $group;
            }
        }

        return $filteredGroups;
    }

    private function getAllCustomerGroups(): array
    {
        $sql = new DbQuery();
        $sql->select('g.id_group, gl.name');
        $sql->from('group', 'g');
        $sql->innerJoin(
            'group_lang',
            'gl',
            'gl.id_group = g.id_group
             AND gl.id_lang = ' . (int) $this->context->language->id
        );
        $sql->orderBy('g.id_group ASC');

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    private function buildMatrix(): array
    {
        $categories = $this->getCategories();
        $brandsByCategory = $this->getBrandsByCategory();
        $discounts = $this->getExistingDiscounts();

        $categoriesById = [];
        $childrenByParent = [];

        foreach ($categories as $category) {
            $idCategory = (int) $category['id_category'];
            $idParent = (int) $category['id_parent'];

            $categoriesById[$idCategory] = $category;
            $childrenByParent[$idParent][] = $idCategory;
        }

        $homeCategoryId = (int) Configuration::get('PS_HOME_CATEGORY');

        if ($homeCategoryId <= 0) {
            $homeCategoryId = 2;
        }

        $matrix = [];

        if (empty($childrenByParent[$homeCategoryId])) {
            return $matrix;
        }

        foreach ($childrenByParent[$homeCategoryId] as $idTopCategory) {
            $this->appendCategoryNode(
                $matrix,
                $idTopCategory,
                $categoriesById,
                $childrenByParent,
                $brandsByCategory,
                $discounts,
                0
            );
        }

        return $matrix;
    }

    private function appendCategoryNode(
        array &$result,
        int $idCategory,
        array $categoriesById,
        array $childrenByParent,
        array $brandsByCategory,
        array $discounts,
        int $level = 0
    ): void {
        if (!isset($categoriesById[$idCategory])) {
            return;
        }

        $category = $categoriesById[$idCategory];

        $brands = [];

        if (isset($brandsByCategory[$idCategory])) {
            foreach ($brandsByCategory[$idCategory] as $brand) {
                $idManufacturer = (int) $brand['id_manufacturer'];

                $brands[] = [
                    'id_manufacturer' => $idManufacturer,
                    'name' => $brand['manufacturer_name'],
                    'discounts' => $discounts[$idCategory][$idManufacturer] ?? [],
                ];
            }
        }

        $children = [];

        if (isset($childrenByParent[$idCategory])) {
            foreach ($childrenByParent[$idCategory] as $idChildCategory) {
                $this->appendCategoryNode(
                    $children,
                    (int) $idChildCategory,
                    $categoriesById,
                    $childrenByParent,
                    $brandsByCategory,
                    $discounts,
                    $level + 1
                );
            }
        }

        if (!empty($brands) || !empty($children)) {
            $result[] = [
                'id_category' => $idCategory,
                'name' => $category['name'],
                'level' => $level,
                'brands' => $brands,
                'children' => $children,
            ];
        }
    }

    private function getCategories(): array
    {
        $sql = new DbQuery();
        $sql->select('c.id_category, c.id_parent, c.level_depth, c.position, cl.name');
        $sql->from('category', 'c');
        $sql->innerJoin(
            'category_lang',
            'cl',
            'cl.id_category = c.id_category
             AND cl.id_lang = ' . (int) $this->context->language->id . '
             AND cl.id_shop = ' . (int) $this->context->shop->id
        );
        $sql->where('c.active = 1');
        $sql->orderBy('c.id_parent ASC, c.position ASC, cl.name ASC');

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    private function getBrandsByCategory(): array
    {
        $sql = new DbQuery();
        $sql->select('DISTINCT cp.id_category, p.id_manufacturer, m.name AS manufacturer_name');
        $sql->from('category_product', 'cp');
        $sql->innerJoin('product', 'p', 'p.id_product = cp.id_product');
        $sql->innerJoin('manufacturer', 'm', 'm.id_manufacturer = p.id_manufacturer');
        $sql->where('p.id_manufacturer > 0');
        $sql->orderBy('m.name ASC');

        $rows = Db::getInstance()->executeS($sql);

        $result = [];

        if (!is_array($rows)) {
            return $result;
        }

        foreach ($rows as $row) {
            $idCategory = (int) $row['id_category'];
            $idManufacturer = (int) $row['id_manufacturer'];

            $result[$idCategory][$idManufacturer] = [
                'id_manufacturer' => $idManufacturer,
                'manufacturer_name' => $row['manufacturer_name'],
            ];
        }

        return $result;
    }

    private function getExistingDiscounts(): array
    {
        $sql = new DbQuery();
        $sql->select('id_category, id_manufacturer, id_group, discount_percent');
        $sql->from('b2b_discount_rule');
        $sql->where('active = 1');

        $rows = Db::getInstance()->executeS($sql);

        $result = [];

        if (!is_array($rows)) {
            return $result;
        }

        foreach ($rows as $row) {
            $result
            [(int) $row['id_category']]
            [(int) $row['id_manufacturer']]
            [(int) $row['id_group']]
                = (float) $row['discount_percent'];
        }

        return $result;
    }
}
