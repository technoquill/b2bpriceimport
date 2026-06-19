<?php

if (!defined('_PS_VERSION_')) {
    exit;
}


if (file_exists(_PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php')) {
    require_once _PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php';
}

use B2B\PriceImport\Config\B2BPriceImportConfig;


/**
 * Class for managing the B2B Discount Matrix in the admin panel.
 *
 * Provides methods for initializing the content of the discount matrix
 * management interface, handling AJAX processes for saving discount rules,
 * and retrieving hierarchical data such as categories, brands, and discounts.
 */
class AdminB2BDiscountMatrixController extends ModuleAdminController
{

    /**
     * Constructor for the AdminB2BDiscountMatrixController class.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * @return B2BPriceImportConfig
     */
//    private function getModuleConfig(): B2BPriceImportConfig
//    {
//        return new B2BPriceImportConfig();
//    }

    /**
     * Initializes the content for the B2B Discount Matrix management page.
     *
     * This method sets up the template and assigns required data such as
     * the discount matrix, customer groups, current index, token, and the
     * AJAX URL for the AdminB2BDiscountMatrix controller. Additionally,
     * it processes the saving of the discount matrix if the related form
     * is submitted.
     *
     * @return void
     */
    public function initContent(): void
    {
        parent::initContent();

        if (Tools::isSubmit('submitB2BDiscountMatrix')) {
            $this->processSaveMatrix();
        }

        $this->context->smarty->assign([
            'matrix' => $this->buildMatrix(),
            'groups' => $this->getCustomerGroups(),
            'currentIndex' => self::$currentIndex,
            'token' => $this->token,
            'ajaxUrl' => $this->context->link->getAdminLink('AdminB2BDiscountMatrix', true),
        ]);

        $this->setTemplate('tabs/discount_matrix.tpl');
    }


    /**
     * Handles the AJAX request to save or remove a discount rule for a specific category, manufacturer, and group.
     * This method validates the provided input values, updates or inserts a discount rule in the database,
     * or deletes an existing rule if the discount value is not provided. Returns a JSON response with the operation's result.
     *
     * @return void Outputs a JSON-encoded response indicating success or failure, along with relevant messages and data.
     */
    public function ajaxProcessSaveDiscountRule(): void
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
                'id_category' => (int) $idCategory,
                'id_manufacturer' => (int) $idManufacturer,
                'id_group' => (int) $idGroup,
                'discount_percent' => (float) $discountPercent,
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

    /**
     * Builds a matrix representing the hierarchical structure of categories with their respective brands and discounts.
     *
     * The method fetches categories, brands grouped by category, and existing discounts.
     * It constructs a nested matrix structure, starting from the home category, and includes child categories
     * while associating relevant brands and discount data.
     *
     * @return array The constructed matrix containing categorized data with associated brands and discounts.
     */
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

            if (!isset($childrenByParent[$idParent])) {
                $childrenByParent[$idParent] = [];
            }

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


    /**
     * @param array $result
     * @param $idCategory
     * @param array $categoriesById
     * @param array $childrenByParent
     * @param array $brandsByCategory
     * @param array $discounts
     * @param $level
     * @return void
     */
    private function appendCategoryNode(array &$result, $idCategory, array $categoriesById, array $childrenByParent, array $brandsByCategory, array $discounts, $level = 0): void
    {
        $idCategory = (int) $idCategory;

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
                    $idChildCategory,
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
                'level' => (int) $level,
                'brands' => $brands,
                'children' => $children,
            ];
        }
    }

    /**
     * Retrieves a list of active categories with their associated data, such as ID, parent ID, depth level,
     * position, and name, sorted by parent ID, position, and name.
     *
     * @return array An array of categories, where each category includes its ID, parent ID, depth level, position, and name.
     */
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

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Retrieves a list of manufacturers grouped by categories.
     *
     * @return array An associative array where the keys are category IDs, and the values are arrays of manufacturer data.
     *               Each manufacturer data array includes the manufacturer ID and name.
     */
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

    /**
     * Retrieves a list of customer groups with their IDs and names,
     * ordered by group ID in ascending order.
     *
     * @return array|null An array of customer groups with their IDs and names,
     *                    or null if the query fails.
     */
    private function getCustomerGroups(): ?array
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

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Retrieves the existing discounts from the database.
     *
     * The discounts are retrieved as a multidimensional array indexed by
     * category ID, manufacturer ID, and group ID respectively. Each value
     * represents the discount percentage.
     *
     * @return float[][][] Multidimensional array of discounts indexed by
     *                     category ID, manufacturer ID, and group ID.
     */
    private function getExistingDiscounts(): array
    {
        $sql = new DbQuery();
        $sql->select('id_category, id_manufacturer, id_group, discount_percent');
        $sql->from('b2b_discount_rule');
        $sql->where('active = 1');

        $rows = Db::getInstance()->executeS($sql);

        $result = [];

        foreach ($rows as $row) {
            $result
            [(int) $row['id_category']]
            [(int) $row['id_manufacturer']]
            [(int) $row['id_group']]
                = (float) $row['discount_percent'];
        }

        return $result;
    }

    /**
     * Processes and saves the discount matrix data into the database.
     * Validates input data to ensure proper structure and acceptable discount values.
     * Deletes the existing discount rules and inserts new ones based on the provided matrix.
     * Rolls back the transaction in case of any errors.
     *
     * @return void True if the discount matrix is successfully saved, False otherwise.
     */
    private function processSaveMatrix(): void
    {
        $discounts = Tools::getValue('discount', []);

        if (!is_array($discounts)) {
            $this->errors[] = $this->l('Invalid discount matrix data.');
            return;
        }

        Db::getInstance()->execute('START TRANSACTION');

        try {
            Db::getInstance()->delete('b2b_discount_rule');

            foreach ($discounts as $idCategory => $brands) {
                if (!is_array($brands)) {
                    continue;
                }

                foreach ($brands as $idManufacturer => $groups) {
                    if (!is_array($groups)) {
                        continue;
                    }

                    foreach ($groups as $idGroup => $value) {
                        $value = trim((string) $value);

                        if ($value === '') {
                            continue;
                        }

                        $discountPercent = (float) str_replace(',', '.', $value);

                        if ($discountPercent < 0 || $discountPercent > 100) {
                            throw new Exception('Discount must be between 0 and 100.');
                        }

                        Db::getInstance()->insert('b2b_discount_rule', [
                            'id_category' => (int) $idCategory,
                            'id_manufacturer' => (int) $idManufacturer,
                            'id_group' => (int) $idGroup,
                            'discount_percent' => (float) $discountPercent,
                            'active' => 1,
                            'date_add' => date('Y-m-d H:i:s'),
                            'date_upd' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            Db::getInstance()->execute('COMMIT');

            $this->confirmations[] = $this->l('Discount matrix saved successfully.');

            return;
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');

            $this->errors[] = $e->getMessage();

            return;
        }
    }
}