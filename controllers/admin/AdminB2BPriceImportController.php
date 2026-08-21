<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(_PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php')) {
    require_once _PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php';
}

use B2B\PriceImport\Config\B2BPriceImportConfig;
use B2B\PriceImport\Constant\ImportStatus;
use B2B\PriceImport\DTO\ImportRunOptions;
use B2B\PriceImport\Repository\AuditLogRepository;
use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;
use B2B\PriceImport\Repository\ImportRepository;
use B2B\PriceImport\Service\AuditLogService;
use B2B\PriceImport\Service\ImportFileStorageService;
use B2B\PriceImport\Service\PriceImportRunService;

class AdminB2BPriceImportController extends ModuleAdminController
{
    private const EXISTING_IMPORT_FILE_LIMIT = 200;
    private const IMPORT_ITEMS_PAGE_SIZES = [20, 50, 100, 300, 1000];
    private const DEFAULT_IMPORT_ITEMS_PAGE_SIZE = 50;
    private const LOG_PAGE_SIZES = [20, 50, 100, 300];
    private const DEFAULT_LOG_PAGE_SIZE = 50;
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
            $importApiKey = $configRepository->getImportApiKey();
            $importUploadBaseUrl = $this->buildImportUploadUrl();
            $importTriggerBaseUrl = $this->buildImportTriggerUrl();
            $importCliCommand = $this->buildImportCliCommand();
            $assign['configGroups'] = array_merge(
                $configRepository->getGroupedDefinitions(B2BPriceImportConfig::SECTION_IMPORT),
                $configRepository->getGroupedDefinitions(B2BPriceImportConfig::SECTION_LOGGING),
                $configRepository->getGroupedDefinitions(B2BPriceImportConfig::SECTION_DISCOUNT_MATRIX)
            );
            $assign['allGroups'] = $this->getAllCustomerGroups();
            $assign['importCliCommand'] = $importCliCommand;
            $assign['importCliHelpCommand'] = $importCliCommand . ' --help';
            $assign['importUploadBaseUrl'] = $importUploadBaseUrl;
            $assign['importUploadUrl'] = $this->buildImportUploadUrl($importApiKey);
            $assign['importTriggerBaseUrl'] = $importTriggerBaseUrl;
            $assign['importTriggerUrl'] = $this->buildImportTriggerUrl($importApiKey);
            $assign['importTriggerHasKey'] = $importApiKey !== '';
        }

        if ($activeSection === 'discount_matrix') {
            $assign['matrix'] = $this->buildMatrix();
            $assign['groups'] = $this->getCustomerGroups();
        }

        if ($activeSection === 'import') {
            $repository = $this->getImportRepository();
            $recentImports = $repository->getLastImports(20);

            $assign['imports'] = $this->addImportFileAvailability($recentImports);
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

        if ($activeSection === 'logs') {
            $assign = array_merge($assign, $this->buildLogsViewData($baseUrl));
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
            $repository = $this->getConfigRepository();
            $before = $repository->get($key);
            $savedValue = $repository->save($key, $value);

            $this->getAuditLogService()->record(
                'config.updated',
                'config',
                'success',
                'Module configuration updated.',
                $key,
                $this->buildConfigAuditValue($key, $before),
                $this->buildConfigAuditValue($key, $savedValue),
                [],
                'admin'
            );

            die(json_encode([
                'success' => true,
                'message' => 'Configuration saved.',
                'value' => $savedValue,
            ]));
        } catch (Throwable $e) {
            $this->getAuditLogService()->record(
                'config.update_failed',
                'config',
                'error',
                $e->getMessage(),
                $key,
                null,
                null,
                [],
                'admin'
            );

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

            $this->getAuditLogService()->record(
                'config.api_key_generated',
                'config',
                'success',
                'A new import API key was generated.',
                B2BPriceImportConfig::IMPORT_API_KEY,
                null,
                ['configured' => true],
                [],
                'admin'
            );

            die(json_encode([
                'success' => true,
                'message' => 'A new import URL access key was generated and saved.',
                'value' => $key,
            ]));
        } catch (Throwable $e) {
            $this->getAuditLogService()->record(
                'config.api_key_generation_failed',
                'config',
                'error',
                $e->getMessage(),
                B2BPriceImportConfig::IMPORT_API_KEY,
                null,
                null,
                [],
                'admin'
            );

            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessUploadImportFile()
    {
        header('Content-Type: application/json');

        try {
            if (empty($_FILES['import_file'])) {
                throw new Exception('Import file is required.');
            }

            $employeeId = isset($this->context->employee->id) ? (int) $this->context->employee->id : null;
            $createData = (new ImportFileStorageService())->storeUploadedCsv($_FILES['import_file'], $employeeId);

            $this->getAuditLogService()->record(
                'file.uploaded',
                'file',
                'success',
                'CSV file uploaded.',
                (string) $createData->storedFilename,
                null,
                [
                    'original_filename' => $createData->originalFilename,
                    'stored_filename' => $createData->storedFilename,
                    'file_size' => $createData->fileSize,
                    'file_hash' => $createData->fileHash,
                ],
                [],
                'admin'
            );

            die(json_encode([
                'success' => true,
                'message' => 'CSV file uploaded. The import was not started.',
                'file' => [
                    'stored_filename' => (string) $createData->storedFilename,
                    'display_filename' => (string) ($createData->originalFilename ?: $createData->storedFilename),
                    'file_size' => (int) ($createData->fileSize ?? 0),
                    'file_size_display' => $this->formatFileSize((int) ($createData->fileSize ?? 0)),
                    'modified_at_display' => date('Y-m-d H:i:s'),
                ],
            ]));
        } catch (Throwable $e) {
            $originalFilename = isset($_FILES['import_file']['name'])
                ? basename((string) $_FILES['import_file']['name'])
                : null;
            $this->getAuditLogService()->record(
                'file.upload_failed',
                'file',
                'error',
                $e->getMessage(),
                $originalFilename,
                null,
                null,
                ['original_filename' => $originalFilename],
                'admin'
            );

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

            if ($idImport <= 0) {
                $this->getAuditLogService()->record(
                    'import.run_request_failed',
                    'import',
                    'error',
                    $e->getMessage(),
                    null,
                    null,
                    null,
                    [],
                    'admin'
                );
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

            $this->getAuditLogService()->record(
                'file.import_failed',
                'file',
                'error',
                $e->getMessage(),
                $storedFilename !== '' ? $storedFilename : null,
                null,
                null,
                ['id_import' => $idImport > 0 ? $idImport : null],
                'admin'
            );

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

        try {
            if ($idImport <= 0) {
                throw new Exception('Invalid import id.');
            }

            $repository = $this->getImportRepository();
            $import = $repository->find($idImport);

            if ($import === null) {
                throw new Exception('Import not found.');
            }

            $repository->deleteImport($idImport);

            $this->getAuditLogService()->record(
                'import.deleted',
                'import',
                'success',
                'Import deleted. The stored CSV file was kept.',
                (string) $idImport,
                [
                    'status' => $import['status'] ?? null,
                    'filename' => $import['original_filename'] ?? null,
                    'total_rows' => (int) ($import['total_rows'] ?? 0),
                    'success_rows' => (int) ($import['success_rows'] ?? 0),
                    'failed_rows' => (int) ($import['failed_rows'] ?? 0),
                ],
                null,
                [],
                'admin'
            );

            die(json_encode([
                'success' => true,
                'message' => 'Import deleted. The stored CSV file was kept.',
            ]));
        } catch (Throwable $e) {
            $this->getAuditLogService()->record(
                'import.delete_failed',
                'import',
                'error',
                $e->getMessage(),
                $idImport > 0 ? (string) $idImport : null,
                null,
                null,
                [],
                'admin'
            );

            die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessDeleteStoredImportFile()
    {
        header('Content-Type: application/json');

        $storedFilename = trim((string) Tools::getValue('stored_filename'));

        try {
            $storage = new ImportFileStorageService();
            $fileData = $storage->createDataFromStoredCsv($storedFilename, null);
            $repository = $this->getImportRepository();
            $import = $repository->findByFilePath((string) $fileData->filePath);

            if (
                is_array($import)
                && in_array((string) ($import['status'] ?? ''), ImportStatus::activeStatuses(), true)
            ) {
                throw new Exception('The file cannot be deleted while its import is active.');
            }

            $storage->deleteStoredFile($fileData->filePath);

            $this->getAuditLogService()->record(
                'file.deleted',
                'file',
                'success',
                'Stored CSV file deleted.',
                $storedFilename,
                [
                    'stored_filename' => $storedFilename,
                    'file_size' => $fileData->fileSize,
                    'file_hash' => $fileData->fileHash,
                ],
                null,
                [
                    'id_import' => is_array($import) ? (int) $import['id_b2b_import'] : null,
                ],
                'admin'
            );

            die(json_encode([
                'success' => true,
                'message' => is_array($import)
                    ? 'Stored CSV file deleted. Linked import history was kept.'
                    : 'Stored CSV file deleted.',
                'stored_filename' => $storedFilename,
                'id_import' => is_array($import) ? (int) $import['id_b2b_import'] : null,
            ]));
        } catch (Throwable $e) {
            $this->getAuditLogService()->record(
                'file.delete_failed',
                'file',
                'error',
                $e->getMessage(),
                $storedFilename !== '' ? $storedFilename : null,
                null,
                null,
                [],
                'admin'
            );

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
        $entityId = $idCategory . ':' . $idManufacturer . ':' . $idGroup;

        if ($idCategory <= 0 || $idManufacturer <= 0 || $idGroup <= 0) {
            $this->getAuditLogService()->record(
                'discount_rule.save_failed',
                'discount_rule',
                'error',
                'Invalid category, brand or group.',
                $entityId,
                null,
                null,
                [],
                'admin'
            );

            die(json_encode([
                'success' => false,
                'message' => 'Invalid category, brand or group.',
            ]));
        }

        try {
            $existingRule = Db::getInstance()->getRow(
                'SELECT *
                 FROM `' . _DB_PREFIX_ . 'b2b_discount_rule`
                 WHERE id_category = ' . (int) $idCategory . '
                   AND id_manufacturer = ' . (int) $idManufacturer . '
                   AND id_group = ' . (int) $idGroup
            );
            $existingRule = is_array($existingRule) ? $existingRule : null;

            if ($rawValue === '') {
                $deleted = Db::getInstance()->delete(
                    'b2b_discount_rule',
                    'id_category = ' . (int) $idCategory .
                    ' AND id_manufacturer = ' . (int) $idManufacturer .
                    ' AND id_group = ' . (int) $idGroup
                );

                if ($existingRule !== null && !$deleted) {
                    throw new RuntimeException('Cannot remove discount rule.');
                }

                $this->getAuditLogService()->record(
                    'discount_rule.deleted',
                    'discount_rule',
                    'success',
                    $existingRule !== null ? 'Discount rule removed.' : 'Discount rule was already absent.',
                    $entityId,
                    $existingRule,
                    null,
                    [],
                    'admin'
                );

                die(json_encode([
                    'success' => true,
                    'message' => 'Rule removed.',
                    'value' => '',
                ]));
            }

            $discountPercent = (float) str_replace(',', '.', $rawValue);

            if ($discountPercent < 0 || $discountPercent > 100) {
                $this->getAuditLogService()->record(
                    'discount_rule.save_failed',
                    'discount_rule',
                    'error',
                    'Discount must be between 0 and 100.',
                    $entityId,
                    $existingRule,
                    null,
                    ['submitted_value' => $rawValue],
                    'admin'
                );

                die(json_encode([
                    'success' => false,
                    'message' => 'Discount must be between 0 and 100.',
                ]));
            }

            $existingId = (int) ($existingRule['id_b2b_discount_rule'] ?? 0);

            $data = [
                'id_category' => $idCategory,
                'id_manufacturer' => $idManufacturer,
                'id_group' => $idGroup,
                'discount_percent' => $discountPercent,
                'active' => 1,
                'date_upd' => date('Y-m-d H:i:s'),
            ];

            if ($existingId > 0) {
                $saved = Db::getInstance()->update(
                    'b2b_discount_rule',
                    $data,
                    'id_b2b_discount_rule = ' . (int) $existingId
                );
            } else {
                $data['date_add'] = date('Y-m-d H:i:s');

                $saved = Db::getInstance()->insert('b2b_discount_rule', $data);
            }

            if (!$saved) {
                throw new RuntimeException('Cannot save discount rule.');
            }

            $savedRule = Db::getInstance()->getRow(
                'SELECT *
                 FROM `' . _DB_PREFIX_ . 'b2b_discount_rule`
                 WHERE id_category = ' . (int) $idCategory . '
                   AND id_manufacturer = ' . (int) $idManufacturer . '
                   AND id_group = ' . (int) $idGroup
            );

            $this->getAuditLogService()->record(
                $existingId > 0 ? 'discount_rule.updated' : 'discount_rule.created',
                'discount_rule',
                'success',
                $existingId > 0 ? 'Discount rule updated.' : 'Discount rule created.',
                $entityId,
                $existingRule,
                is_array($savedRule) ? $savedRule : $data,
                [],
                'admin'
            );

            die(json_encode([
                'success' => true,
                'message' => 'Saved.',
                'value' => number_format($discountPercent, 2, '.', ''),
            ]));
        } catch (Throwable $e) {
            $this->getAuditLogService()->record(
                'discount_rule.save_failed',
                'discount_rule',
                'error',
                $e->getMessage(),
                $entityId,
                null,
                null,
                ['submitted_value' => $rawValue],
                'admin'
            );

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

    private function getAuditLogService(): AuditLogService
    {
        return new AuditLogService();
    }

    private function buildLogsViewData(string $baseUrl): array
    {
        $filters = $this->resolveAuditLogFilters();
        $pageSize = (int) Tools::getValue('logs_per_page', self::DEFAULT_LOG_PAGE_SIZE);

        if (!in_array($pageSize, self::LOG_PAGE_SIZES, true)) {
            $pageSize = self::DEFAULT_LOG_PAGE_SIZE;
        }

        $data = [
            'logs' => [],
            'logsError' => null,
            'logFilters' => $filters,
            'logActions' => [],
            'logEntityTypes' => [],
            'logResults' => [],
            'logChannels' => [],
            'logsResetUrl' => $baseUrl . '&section=logs',
            'logsFormAction' => $this->getLogsFormAction($baseUrl),
            'logsFormHiddenFields' => $this->getLogsFormHiddenFields($baseUrl),
            'logsPagination' => $this->buildLogsPagination(
                $baseUrl,
                1,
                $pageSize,
                0,
                1,
                $filters
            ),
        ];

        try {
            $this->getAuditLogService()->purgeExpired();
            $repository = new AuditLogRepository();
            $totalItems = $repository->count($filters);
            $totalPages = max(1, (int) ceil($totalItems / $pageSize));
            $currentPage = max(1, (int) Tools::getValue('logs_page', 1));
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * $pageSize;

            $data['logs'] = $this->formatAuditLogRows(
                $repository->findPage($pageSize, $offset, $filters)
            );
            $data['logActions'] = $repository->getDistinctValues('action');
            $data['logEntityTypes'] = $repository->getDistinctValues('entity_type');
            $data['logResults'] = $repository->getDistinctValues('result');
            $data['logChannels'] = $repository->getDistinctValues('channel');
            $data['logsPagination'] = $this->buildLogsPagination(
                $baseUrl,
                $currentPage,
                $pageSize,
                $totalItems,
                $totalPages,
                $filters
            );
        } catch (Throwable $exception) {
            $data['logsError'] = $exception->getMessage();
        }

        return $data;
    }

    private function getLogsFormAction(string $baseUrl): string
    {
        $parts = explode('?', $baseUrl, 2);

        return $parts[0];
    }

    private function getLogsFormHiddenFields(string $baseUrl): array
    {
        $parts = explode('?', $baseUrl, 2);
        $parameters = [];

        if (isset($parts[1])) {
            parse_str($parts[1], $parameters);
        }

        unset($parameters['section']);

        $fields = [];

        foreach ($parameters as $name => $value) {
            if (is_scalar($value)) {
                $fields[] = ['name' => (string) $name, 'value' => (string) $value];
            }
        }

        return $fields;
    }

    private function resolveAuditLogFilters(): array
    {
        $filters = [];
        $parameters = [
            'date_from' => ['parameter' => 'logs_date_from', 'max_length' => 10],
            'date_to' => ['parameter' => 'logs_date_to', 'max_length' => 10],
            'entity_type' => ['parameter' => 'logs_entity_type', 'max_length' => 32],
            'result' => ['parameter' => 'logs_result', 'max_length' => 16],
            'channel' => ['parameter' => 'logs_channel', 'max_length' => 32],
            'action' => ['parameter' => 'logs_action', 'max_length' => 96],
            'actor' => ['parameter' => 'logs_actor', 'max_length' => 255],
            'search' => ['parameter' => 'logs_search', 'max_length' => 255],
        ];

        foreach ($parameters as $filter => $definition) {
            $value = Tools::getValue($definition['parameter'], '');
            $value = is_string($value) ? trim($value) : '';
            $filters[$filter] = substr($value, 0, (int) $definition['max_length']);
        }

        foreach (['date_from', 'date_to'] as $dateFilter) {
            if (!$this->isAuditLogDate($filters[$dateFilter])) {
                $filters[$dateFilter] = '';
            }
        }

        return $filters;
    }

    private function formatAuditLogRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['before_display'] = $this->formatAuditLogJson($row['before_json'] ?? null);
            $row['after_display'] = $this->formatAuditLogJson($row['after_json'] ?? null);
            $row['context_display'] = $this->formatAuditLogJson($row['context_json'] ?? null);
            $row['has_details'] = $row['before_display'] !== ''
                || $row['after_display'] !== ''
                || $row['context_display'] !== '';
            $row['actor_display'] = trim((string) ($row['actor_name'] ?? ''));

            if ($row['actor_display'] === '') {
                $row['actor_display'] = ucfirst((string) ($row['actor_type'] ?? 'system'));
            }

            $row['result_class'] = match ((string) ($row['result'] ?? '')) {
                'success' => 'label-success',
                'warning' => 'label-warning',
                'error' => 'label-danger',
                default => 'label-default',
            };
        }
        unset($row);

        return $rows;
    }

    private function formatAuditLogJson($rawValue): string
    {
        $rawValue = trim((string) $rawValue);

        if ($rawValue === '') {
            return '';
        }

        $decoded = json_decode($rawValue, true);

        if (!is_array($decoded)) {
            return $rawValue;
        }

        $encoded = json_encode(
            $decoded,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        return is_string($encoded) ? $encoded : $rawValue;
    }

    private function buildLogsPagination(
        string $baseUrl,
        int $currentPage,
        int $pageSize,
        int $totalItems,
        int $totalPages,
        array $filters
    ): array {
        $pageSizeOptions = [];

        foreach (self::LOG_PAGE_SIZES as $option) {
            $pageSizeOptions[] = [
                'value' => $option,
                'is_current' => $option === $pageSize,
                'url' => $this->buildLogsUrl($baseUrl, 1, $option, $filters),
            ];
        }

        $pages = [];
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        for ($page = $startPage; $page <= $endPage; $page++) {
            $pages[] = [
                'number' => $page,
                'is_current' => $page === $currentPage,
                'url' => $this->buildLogsUrl($baseUrl, $page, $pageSize, $filters),
            ];
        }

        return [
            'current_page' => $currentPage,
            'page_size' => $pageSize,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'first_item' => $totalItems > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0,
            'last_item' => min($currentPage * $pageSize, $totalItems),
            'previous_url' => $currentPage > 1
                ? $this->buildLogsUrl($baseUrl, $currentPage - 1, $pageSize, $filters)
                : null,
            'next_url' => $currentPage < $totalPages
                ? $this->buildLogsUrl($baseUrl, $currentPage + 1, $pageSize, $filters)
                : null,
            'pages' => $pages,
            'page_size_options' => $pageSizeOptions,
        ];
    }

    private function buildLogsUrl(
        string $baseUrl,
        int $page,
        int $pageSize,
        array $filters
    ): string {
        $parameters = [
            'section' => 'logs',
            'logs_page' => max(1, $page),
            'logs_per_page' => $pageSize,
        ];
        $filterParameters = [
            'date_from' => 'logs_date_from',
            'date_to' => 'logs_date_to',
            'entity_type' => 'logs_entity_type',
            'result' => 'logs_result',
            'channel' => 'logs_channel',
            'action' => 'logs_action',
            'actor' => 'logs_actor',
            'search' => 'logs_search',
        ];

        foreach ($filterParameters as $filter => $parameter) {
            if (($filters[$filter] ?? '') !== '') {
                $parameters[$parameter] = $filters[$filter];
            }
        }

        return $baseUrl . '&' . http_build_query($parameters);
    }

    private function isAuditLogDate(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function buildConfigAuditValue(string $key, $value): array
    {
        if ($key === B2BPriceImportConfig::IMPORT_API_KEY) {
            return ['configured' => trim((string) $value) !== ''];
        }

        return ['value' => $value];
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

    private function buildImportTriggerUrl(string $accessKey = ''): string
    {
        $parameters = $accessKey !== '' ? ['key' => $accessKey] : [];

        return $this->context->link->getModuleLink(
            'b2bpriceimport',
            'import',
            $parameters,
            true
        );
    }

    private function buildImportUploadUrl(string $accessKey = ''): string
    {
        $parameters = $accessKey !== '' ? ['key' => $accessKey] : [];

        return $this->context->link->getModuleLink(
            'b2bpriceimport',
            'upload',
            $parameters,
            true
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

        $configRepository = $this->getConfigRepository();
        $summary = (new PriceImportRunService())->run(new ImportRunOptions(
            importId: $idImport,
            type: 'all',
            batchLimit: $configRepository->getImportBatchLimit(),
            timeLimit: null,
            lockTtl: $configRepository->getImportLockTtl(),
            forceLock: false,
            scanDirectory: $configRepository->getImportScanDir(),
            maxFileAgeHours: $configRepository->getImportMaxFileAgeHours(),
            scanLimit: $configRepository->getImportScanLimit()
        ));

        if (!$summary['success']) {
            throw new RuntimeException((string) ($summary['message'] ?? 'Import failed.'));
        }

        $parseResult = is_array($summary['parse']) ? $summary['parse'] : [];
        $processResult = is_array($summary['process']) ? $summary['process'] : [];

        $failedRows = (int) ($parseResult['failed'] ?? 0)
            + (int) ($processResult['failed'] ?? 0);

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
            $file['file_size_display'] = $this->formatFileSize((int) ($file['file_size'] ?? 0));
            $file['modified_at_display'] = date('Y-m-d H:i:s', (int) $file['modified_at']);
            $file['can_delete'] = !is_array($import)
                || !in_array((string) $import['status'], ImportStatus::activeStatuses(), true);
        }
        unset($file);

        return $files;
    }

    private function addImportFileAvailability(array $imports): array
    {
        foreach ($imports as &$import) {
            $filePath = trim((string) ($import['file_path'] ?? ''));
            $import['file_available'] = $filePath !== '' && is_file($filePath) && is_readable($filePath);
        }
        unset($import);

        return $imports;
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;

        foreach ($units as $index => $unit) {
            if ($size < 1024 || $index === count($units) - 1) {
                return number_format($size, $size >= 10 ? 1 : 2, '.', '') . ' ' . $unit;
            }

            $size /= 1024;
        }

        return $bytes . ' B';
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
