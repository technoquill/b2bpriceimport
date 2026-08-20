<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(_PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php')) {
    require_once _PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php';
}

use B2B\PriceImport\DTO\ImportRunOptions;
use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;
use B2B\PriceImport\Service\PriceImportRunService;

class B2bPriceImportImportModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

    public function __construct()
    {
        parent::__construct();

        $this->ajax = true;
    }

    public function postProcess()
    {
        parent::postProcess();

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if (!in_array($method, ['GET', 'POST'], true)) {
            header('Allow: GET, POST');
            $this->sendJsonResponse([
                'success' => false,
                'error_code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only GET and POST requests are allowed.',
            ], 405);
        }

        try {
            $configRepository = new B2BPriceImportConfigRepository();

            if (!$configRepository->isImportApiKeyValid($this->getProvidedAccessKey())) {
                $this->sendJsonResponse([
                    'success' => false,
                    'error_code' => 'INVALID_ACCESS_KEY',
                    'message' => 'Invalid import access key.',
                ], 401);
            }

            ignore_user_abort(true);
            @set_time_limit(0);

            $summary = (new PriceImportRunService())->run(new ImportRunOptions(
                importId: null,
                type: $configRepository->getImportRunType(),
                batchLimit: $configRepository->getImportBatchLimit(),
                timeLimit: $configRepository->getImportTimeLimit(),
                lockTtl: $configRepository->getImportLockTtl(),
                forceLock: false,
                scanDirectory: $configRepository->getImportScanDir(),
                maxFileAgeHours: $configRepository->getImportMaxFileAgeHours(),
                scanLimit: $configRepository->getImportScanLimit(),
                filename: $this->getRequestedFilename()
            ));

            $statusCode = 200;

            if (!$summary['success']) {
                $statusCode = match ($summary['error_code'] ?? null) {
                    PriceImportRunService::ERROR_LOCKED => 409,
                    PriceImportRunService::ERROR_INVALID_OPTIONS => 400,
                    default => 500,
                };
            }

            $this->sendJsonResponse($this->buildPublicSummary($summary), $statusCode);
        } catch (InvalidArgumentException $exception) {
            $this->sendJsonResponse([
                'success' => false,
                'error_code' => PriceImportRunService::ERROR_INVALID_OPTIONS,
                'message' => $exception->getMessage(),
            ], 400);
        } catch (Throwable $exception) {
            $this->sendJsonResponse([
                'success' => false,
                'error_code' => PriceImportRunService::ERROR_FAILED,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    private function getProvidedAccessKey(): string
    {
        $headerKey = $_SERVER['HTTP_X_B2B_IMPORT_KEY'] ?? '';

        if (is_string($headerKey) && trim($headerKey) !== '') {
            return trim($headerKey);
        }

        $requestKey = Tools::getValue('key', '');

        return is_string($requestKey) ? trim($requestKey) : '';
    }

    private function getRequestedFilename(): ?string
    {
        $requestedFilename = Tools::getValue('file');

        if ($requestedFilename === false || $requestedFilename === null) {
            return null;
        }

        if (!is_string($requestedFilename)) {
            throw new InvalidArgumentException('File must be a string.');
        }

        $requestedFilename = trim($requestedFilename);

        return $requestedFilename !== '' ? $requestedFilename : null;
    }

    private function buildPublicSummary(array $summary): array
    {
        $scan = $summary['scan'] ?? null;
        $scanSummary = null;

        if (is_array($scan)) {
            $skippedReasons = [];

            foreach (($scan['skipped'] ?? []) as $skippedFile) {
                $reason = (string) ($skippedFile['reason'] ?? 'unknown');
                $skippedReasons[$reason] = ($skippedReasons[$reason] ?? 0) + 1;
            }

            $scanSummary = [
                'created' => count($scan['created'] ?? []),
                'skipped' => count($scan['skipped'] ?? []),
                'skipped_reasons' => (object) $skippedReasons,
            ];
        }

        return [
            'success' => (bool) ($summary['success'] ?? false),
            'error_code' => $summary['error_code'] ?? null,
            'import_id' => $summary['import_id'] ?? null,
            'type' => $summary['type'] ?? null,
            'file' => $summary['file'] ?? null,
            'scan' => $scanSummary,
            'parse' => $summary['parse'] ?? null,
            'process' => $summary['process'] ?? null,
            'message' => $summary['message'] ?? null,
        ];
    }

    private function sendJsonResponse(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow, noarchive');

        exit((string) json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }
}
