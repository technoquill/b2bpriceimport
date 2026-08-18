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
    private const EXISTING_IMPORT_FILE_LIMIT = 200;
    private const IMPORT_ITEMS_PAGE_SIZES = [20, 50, 100, 300, 1000];
    private const DEFAULT_IMPORT_ITEMS_PAGE_SIZE = 50;
    private const IMPORT_ITEM_FILTER_PARAMETERS = [
        'active' => 'items_active',
        'validation_status' => 'items_validation',
        'processing_status' => 'items_processing',
        'item_status' => 'items_status',
        'error' => 'items_error',
    ];
    private const IMPORT_ITEM_SEARCH_PARAMETERS = [
        'reference' => 'items_reference_search',
        'product_name' => 'items_product_name_search',
    ];
    private const IMPORT_ITEM_SEARCH_MAX_LENGTHS = [
        'reference' => 128,
        'product_name' => 255,
    ];

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
    }

    public function initContent()
    {
        parent::initContent();

        $activeSection = $this->getActiveSection();
        $baseUrl = $this->context->link->getAdminLink('AdminB2BPriceImport', true);

        $assign = [
            'activeSection' => $activeSection,
            'menuItems' => $this->getMenuItems(),
            'ajaxUrl' => $baseUrl,
            'importListUrl' => $baseUrl . '&section=import',
        ];

        if ($activeSection === 'config') {
            $configRepository = $this->getConfigRepository();
            $assign['configDefinitions'] = $configRepository->getDefinitions();
            $assign['allGroups'] = $this->getAllCustomerGroups();
            $assign['importCliCommand'] = $this->buildImportCliCommand();
        }

        if ($activeSection === 'discount_matrix') {
            $assign['matrix'] = $this->buildMatrix();
            $assign['groups'] = $this->getCustomerGroups();
        }

        if ($activeSection === 'import') {
            $repository = $this->getImportRepository();

            $assign['imports'] = $repository->getLastImports(20);
            $assign['existingImportFiles'] = $this->getExistingImportFiles(
                $repository->getLastImports(self::EXISTING_IMPORT_FILE_LIMIT)
            );
        }

        if ($activeSection === 'import_detail') {
            $idImport = (int) Tools::getValue('id_import');
            $repository = $this->getImportRepository();
            $import = $idImport > 0 ? $repository->find($idImport) : null;

            $assign['import'] = $import;
            $assign['importJobs'] = $import !== null ? $repository->getImportJobs($idImport) : [];

            if ($import !== null) {
                $pageSize = (int) Tools::getValue(
                    'items_per_page',
                    self::DEFAULT_IMPORT_ITEMS_PAGE_SIZE
                );

                if (!in_array($pageSize, self::IMPORT_ITEMS_PAGE_SIZES, true)) {
                    $pageSize = self::DEFAULT_IMPORT_ITEMS_PAGE_SIZE;
                }

                $filterValues = [];

                foreach (array_keys(self::IMPORT_ITEM_FILTER_PARAMETERS) as $filter) {
                    $filterValues[$filter] = $repository->getImportItemFilterValues($idImport, $filter);
                }

                $selectedFilters = $this->resolveImportItemFilters($filterValues);
                $searchTerms = $this->resolveImportItemSearchTerms();
                $unfilteredTotalItems = $repository->countImportItems($idImport);
                $totalItems = empty($selectedFilters) && empty($searchTerms)
                    ? $unfilteredTotalItems
                    : $repository->countImportItems($idImport, $selectedFilters, $searchTerms);
                $totalPages = max(1, (int) ceil($totalItems / $pageSize));
                $currentPage = max(1, (int) Tools::getValue('items_page', 1));
                $currentPage = min($currentPage, $totalPages);
                $offset = ($currentPage - 1) * $pageSize;

                $assign['importItems'] = $repository->getImportItems(
                    $idImport,
                    $pageSize,
                    $offset,
                    $selectedFilters,
                    $searchTerms
                );
                $assign['importItemsPagination'] = $this->buildImportItemsPagination(
                    $baseUrl,
                    $idImport,
                    $currentPage,
                    $pageSize,
                    $totalItems,
                    $totalPages,
                    $selectedFilters,
                    $searchTerms
                );
                $assign['importItemsFilters'] = $this->buildImportItemFilterSelects(
                    $baseUrl,
                    $idImport,
                    $pageSize,
                    $filterValues,
                    $selectedFilters,
                    $searchTerms
                );
                $assign['importItemsSearches'] = $this->buildImportItemSearchControls(
                    $baseUrl,
                    $idImport,
                    $pageSize,
                    $selectedFilters,
                    $searchTerms
                );
                $assign['importItemsResetUrl'] = $this->buildImportItemsUrl(
                    $baseUrl,
                    $idImport,
                    1,
                    $pageSize,
                    [],
                    []
                );
                $assign['importItemsHasActiveCriteria'] = !empty($selectedFilters) || !empty($searchTerms);
                $assign['importItemsHasRows'] = $unfilteredTotalItems > 0;
            } else {
                $assign['importItems'] = [];
                $assign['importItemsPagination'] = [];
                $assign['importItemsFilters'] = [];
                $assign['importItemsSearches'] = [];
                $assign['importItemsResetUrl'] = '';
                $assign['importItemsHasActiveCriteria'] = false;
                $assign['importItemsHasRows'] = false;
            }
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
            'import_detail',
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
        } catch (Throwable $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessGenerateImportApiKey()
    {
        header('Content-Type: application/json');

        try {
            $key = $this->getConfigRepository()->generateImportApiKey();

            die(json_encode([
                'success' => true,
                'message' => 'A new import URL access key was generated and saved.',
                'value' => $key,
            ]));
        } catch (Throwable $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessCreateImport()
    {
        header('Content-Type: application/json');

        $idImport = 0;

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
            $result = $this->runImportToCompletion($idImport);

            die(json_encode([
                'success' => true,
                'message' => 'CSV file uploaded and import processed.',
                'id_import' => $idImport,
                'parse' => $result['parse'],
                'process' => $result['process'],
            ]));
        } catch (Throwable $e) {
            if ($idImport > 0) {
                try {
                    $this->getImportRepository()->setStatus($idImport, 'failed', $e->getMessage());
                } catch (Throwable $innerException) {
                    // Keep the AJAX response valid even if updating the import status fails.
                }
            }

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

            $result = $this->runImportToCompletion($idImport);

            die(json_encode([
                'success' => true,
                'message' => 'Import processed.',
                'parse' => $result['parse'],
                'process' => $result['process'],
            ]));
        } catch (Throwable $e) {
            if ($idImport > 0) {
                try {
                    $this->getImportRepository()->setStatus($idImport, 'failed', $e->getMessage());
                } catch (Throwable $innerException) {
                    // Keep the AJAX response valid even if updating the import status fails.
                }
            }

            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessRunStoredImport()
    {
        header('Content-Type: application/json');

        $storedFilename = (string) Tools::getValue('stored_filename');
        $idImport = 0;

        try {
            $employeeId = isset($this->context->employee->id) ? (int) $this->context->employee->id : null;
            $createData = (new ImportFileStorageService())->createDataFromStoredCsv(
                $storedFilename,
                $employeeId
            );

            $repository = $this->getImportRepository();
            $import = $repository->findByFilePath((string) $createData->filePath);

            if ($import === null && $createData->fileHash !== null) {
                $importWithSameHash = $repository->findByFileHash($createData->fileHash);
                $registeredFilePath = is_array($importWithSameHash)
                    ? realpath((string) ($importWithSameHash['file_path'] ?? ''))
                    : false;

                if ($registeredFilePath === $createData->filePath) {
                    $import = $importWithSameHash;
                }
            }

            $created = $import === null;

            if ($created) {
                $idImport = $repository->create($createData);
                $repository->createJob($idImport, 'parse');
                $repository->createJob($idImport, 'process');
            } else {
                $idImport = (int) $import['id_b2b_import'];
            }

            $result = $this->runImportToCompletion($idImport);

            die(json_encode([
                'success' => true,
                'message' => $created
                    ? 'Stored CSV file registered and processed.'
                    : 'Stored CSV file processed.',
                'id_import' => $idImport,
                'parse' => $result['parse'],
                'process' => $result['process'],
            ]));
        } catch (Throwable $e) {
            if ($idImport > 0) {
                try {
                    $this->getImportRepository()->setStatus($idImport, 'failed', $e->getMessage());
                } catch (Throwable $innerException) {
                    // Keep the AJAX response valid even if updating the import status fails.
                }
            }

            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessDeleteImport()
    {
        header('Content-Type: application/json');

        $idImport = (int) Tools::getValue('id_import');
        $deleteFile = (string) Tools::getValue('delete_file', '0') === '1';

        try {
            if ($idImport <= 0) {
                throw new Exception('Invalid import id.');
            }

            $repository = $this->getImportRepository();
            $import = $repository->find($idImport);

            if ($import === null) {
                throw new Exception('Import not found.');
            }

            if ($deleteFile) {
                (new ImportFileStorageService())->deleteStoredFile($import['file_path'] ?? null);
            }

            $repository->deleteImport($idImport);

            die(json_encode([
                'success' => true,
                'message' => $deleteFile
                    ? 'Import and its stored CSV file deleted.'
                    : 'Import deleted. The stored CSV file was kept.',
            ]));
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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

    private function buildImportCliCommand(): string
    {
        $consolePath = rtrim((string) _PS_ROOT_DIR_, '/\\') . DIRECTORY_SEPARATOR . 'bin/console';
        $phpBinary = $this->resolveCliPhpBinary();
        $phpCommand = $phpBinary === 'php' ? $phpBinary : escapeshellarg($phpBinary);
        $phpIni = php_ini_loaded_file();
        $phpIniOption = $phpBinary !== 'php' && is_string($phpIni) && is_readable($phpIni)
            ? ' -c ' . escapeshellarg($phpIni)
            : '';

        return sprintf(
            '%s%s %s b2b:price-import:run --env=prod --no-debug',
            $phpCommand,
            $phpIniOption,
            escapeshellarg($consolePath)
        );
    }

    private function resolveCliPhpBinary(): string
    {
        $runtimeBinary = trim((string) PHP_BINARY);
        $binaryDirectory = rtrim((string) PHP_BINDIR, '/\\');
        $candidates = [];

        if ($runtimeBinary !== '') {
            if (PHP_SAPI === 'cli') {
                $candidates[] = $runtimeBinary;
            }

            $candidates[] = dirname($runtimeBinary) . DIRECTORY_SEPARATOR . 'php';
        }

        if ($binaryDirectory !== '') {
            $candidates[] = $binaryDirectory . DIRECTORY_SEPARATOR . 'php';
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }

    private function runImportToCompletion(int $idImport): array
    {
        ignore_user_abort(true);
        @set_time_limit(0);

        $parseResult = (new PriceImportParser())->parse($idImport);
        $batchLimit = max(1, $this->getConfigRepository()->getImportBatchLimit());
        $processor = new PriceImportProcessor();
        $processResult = [
            'processed' => 0,
            'failed' => 0,
            'batches' => 0,
        ];

        do {
            $batchResult = $processor->process($idImport, $batchLimit);
            $handledRows = (int) ($batchResult['processed'] ?? 0)
                + (int) ($batchResult['failed'] ?? 0);

            $processResult['processed'] += (int) ($batchResult['processed'] ?? 0);
            $processResult['failed'] += (int) ($batchResult['failed'] ?? 0);
            $processResult['batches']++;
        } while ($handledRows >= $batchLimit);

        $failedRows = (int) ($parseResult['failed'] ?? 0) + $processResult['failed'];

        if ($failedRows > 0) {
            $this->getImportRepository()->setStatus(
                $idImport,
                'failed',
                sprintf('%d import row(s) failed.', $failedRows)
            );
        }

        return [
            'parse' => $parseResult,
            'process' => $processResult,
        ];
    }

    private function getExistingImportFiles(array $imports): array
    {
        $importsByPath = [];

        foreach ($imports as $import) {
            $filePath = trim((string) ($import['file_path'] ?? ''));
            $realFilePath = $filePath !== '' ? realpath($filePath) : false;

            if ($realFilePath !== false && !isset($importsByPath[$realFilePath])) {
                $importsByPath[$realFilePath] = $import;
            }
        }

        $files = (new ImportFileStorageService())->listStoredCsvFiles();

        foreach ($files as &$file) {
            $import = $importsByPath[$file['file_path']] ?? null;

            $file['display_filename'] = is_array($import) && !empty($import['original_filename'])
                ? (string) $import['original_filename']
                : (string) $file['stored_filename'];
            $file['id_b2b_import'] = is_array($import) ? (int) $import['id_b2b_import'] : null;
            $file['status'] = is_array($import) ? (string) $import['status'] : null;
            $file['date_add'] = is_array($import) ? (string) $import['date_add'] : null;
        }
        unset($file);

        return $files;
    }

    private function buildImportItemsPagination(
        string $baseUrl,
        int $idImport,
        int $currentPage,
        int $pageSize,
        int $totalItems,
        int $totalPages,
        array $selectedFilters,
        array $searchTerms
    ): array
    {
        $visiblePageNumbers = [1, $totalPages];

        for ($pageNumber = $currentPage - 2; $pageNumber <= $currentPage + 2; ++$pageNumber) {
            if ($pageNumber > 0 && $pageNumber <= $totalPages) {
                $visiblePageNumbers[] = $pageNumber;
            }
        }

        $visiblePageNumbers = array_values(array_unique($visiblePageNumbers));
        sort($visiblePageNumbers, SORT_NUMERIC);

        $pages = [];
        $previousVisiblePage = null;

        foreach ($visiblePageNumbers as $pageNumber) {
            if ($previousVisiblePage !== null && $pageNumber > $previousVisiblePage + 1) {
                $pages[] = ['ellipsis' => true];
            }

            $pages[] = [
                'ellipsis' => false,
                'number' => $pageNumber,
                'is_current' => $pageNumber === $currentPage,
                'url' => $this->buildImportItemsUrl(
                    $baseUrl,
                    $idImport,
                    $pageNumber,
                    $pageSize,
                    $selectedFilters,
                    $searchTerms
                ),
            ];
            $previousVisiblePage = $pageNumber;
        }

        $pageSizeOptions = [];

        foreach (self::IMPORT_ITEMS_PAGE_SIZES as $size) {
            $pageSizeOptions[] = [
                'value' => $size,
                'is_current' => $size === $pageSize,
                'url' => $this->buildImportItemsUrl(
                    $baseUrl,
                    $idImport,
                    1,
                    $size,
                    $selectedFilters,
                    $searchTerms
                ),
            ];
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'first_item' => $totalItems > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0,
            'last_item' => min($currentPage * $pageSize, $totalItems),
            'page_size_options' => $pageSizeOptions,
            'pages' => $pages,
            'previous_url' => $currentPage > 1
                ? $this->buildImportItemsUrl(
                    $baseUrl,
                    $idImport,
                    $currentPage - 1,
                    $pageSize,
                    $selectedFilters,
                    $searchTerms
                )
                : null,
            'next_url' => $currentPage < $totalPages
                ? $this->buildImportItemsUrl(
                    $baseUrl,
                    $idImport,
                    $currentPage + 1,
                    $pageSize,
                    $selectedFilters,
                    $searchTerms
                )
                : null,
        ];
    }

    private function resolveImportItemFilters(array $filterValues): array
    {
        $selectedFilters = [];

        foreach (self::IMPORT_ITEM_FILTER_PARAMETERS as $filter => $parameter) {
            $requestedToken = Tools::getValue($parameter, '');

            if (!is_string($requestedToken) || $requestedToken === '') {
                continue;
            }

            foreach ($filterValues[$filter] ?? [] as $value) {
                if (hash_equals($this->getImportItemFilterToken($filter, $value), $requestedToken)) {
                    $selectedFilters[$filter] = $value;
                    break;
                }
            }
        }

        return $selectedFilters;
    }

    private function resolveImportItemSearchTerms(): array
    {
        $searchTerms = [];

        foreach (self::IMPORT_ITEM_SEARCH_PARAMETERS as $search => $parameter) {
            $value = Tools::getValue($parameter, '');

            if (!is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value === '') {
                continue;
            }

            $searchTerms[$search] = Tools::substr(
                $value,
                0,
                self::IMPORT_ITEM_SEARCH_MAX_LENGTHS[$search]
            );
        }

        return $searchTerms;
    }

    private function buildImportItemFilterSelects(
        string $baseUrl,
        int $idImport,
        int $pageSize,
        array $filterValues,
        array $selectedFilters,
        array $searchTerms
    ): array
    {
        $selects = [];

        foreach (array_keys(self::IMPORT_ITEM_FILTER_PARAMETERS) as $filter) {
            $filtersWithoutCurrent = $selectedFilters;
            unset($filtersWithoutCurrent[$filter]);

            $select = [
                'all_url' => $this->buildImportItemsUrl(
                    $baseUrl,
                    $idImport,
                    1,
                    $pageSize,
                    $filtersWithoutCurrent,
                    $searchTerms
                ),
                'is_all' => !array_key_exists($filter, $selectedFilters),
                'options' => [],
            ];

            foreach ($filterValues[$filter] ?? [] as $value) {
                $optionFilters = $selectedFilters;
                $optionFilters[$filter] = $value;

                $select['options'][] = [
                    'label' => $this->getImportItemFilterLabel($filter, $value),
                    'is_current' => array_key_exists($filter, $selectedFilters)
                        && $selectedFilters[$filter] === $value,
                    'url' => $this->buildImportItemsUrl(
                        $baseUrl,
                        $idImport,
                        1,
                        $pageSize,
                        $optionFilters,
                        $searchTerms
                    ),
                ];
            }

            $selects[$filter] = $select;
        }

        return $selects;
    }

    private function buildImportItemSearchControls(
        string $baseUrl,
        int $idImport,
        int $pageSize,
        array $selectedFilters,
        array $searchTerms
    ): array
    {
        $controls = [];

        foreach (self::IMPORT_ITEM_SEARCH_PARAMETERS as $search => $parameter) {
            $otherSearchTerms = $searchTerms;
            unset($otherSearchTerms[$search]);

            $controls[$search] = [
                'value' => $searchTerms[$search] ?? '',
                'parameter' => $parameter,
                'base_url' => $this->buildImportItemsUrl(
                    $baseUrl,
                    $idImport,
                    1,
                    $pageSize,
                    $selectedFilters,
                    $otherSearchTerms
                ),
            ];
        }

        return $controls;
    }

    private function getImportItemFilterLabel(string $filter, string $value): string
    {
        if ($value === '') {
            return $filter === 'error' ? $this->l('No error') : $this->l('Empty');
        }

        if ($filter === 'active') {
            if ($value === '1') {
                return $this->l('Yes');
            }

            if ($value === '0') {
                return $this->l('No');
            }
        }

        return $value;
    }

    private function getImportItemFilterToken(string $filter, string $value): string
    {
        return hash('sha256', $filter . "\0" . $value);
    }

    private function buildImportItemsUrl(
        string $baseUrl,
        int $idImport,
        int $page,
        int $pageSize,
        array $selectedFilters,
        array $searchTerms
    ): string
    {
        $url = $baseUrl
            . '&section=import_detail'
            . '&id_import=' . $idImport
            . '&items_page=' . $page
            . '&items_per_page=' . $pageSize;

        foreach (self::IMPORT_ITEM_FILTER_PARAMETERS as $filter => $parameter) {
            if (!array_key_exists($filter, $selectedFilters)) {
                continue;
            }

            $url .= '&' . $parameter . '='
                . $this->getImportItemFilterToken($filter, $selectedFilters[$filter]);
        }

        foreach (self::IMPORT_ITEM_SEARCH_PARAMETERS as $search => $parameter) {
            if (!isset($searchTerms[$search]) || $searchTerms[$search] === '') {
                continue;
            }

            $url .= '&' . $parameter . '=' . rawurlencode($searchTerms[$search]);
        }

        return $url;
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
