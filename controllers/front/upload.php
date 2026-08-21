<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(_PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php')) {
    require_once _PS_MODULE_DIR_ . 'b2bpriceimport/vendor/autoload.php';
}

use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;
use B2B\PriceImport\Service\AuditLogService;
use B2B\PriceImport\Service\ImportFileStorageService;

class B2bPriceImportUploadModuleFrontController extends ModuleFrontController
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

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            (new AuditLogService())->record(
                'file.upload_failed',
                'file',
                'error',
                'Only POST requests are allowed.',
                null,
                null,
                null,
                ['error_code' => 'METHOD_NOT_ALLOWED'],
                'api'
            );
            header('Allow: POST');
            $this->sendJsonResponse([
                'success' => false,
                'error_code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only POST requests are allowed.',
            ], 405);
        }

        try {
            $configRepository = new B2BPriceImportConfigRepository();

            if (!$configRepository->isImportApiKeyValid($this->getProvidedAccessKey())) {
                (new AuditLogService())->record(
                    'system.authentication_failed',
                    'system',
                    'error',
                    'Invalid access key supplied to the CSV upload endpoint.',
                    'csv_upload',
                    null,
                    null,
                    [],
                    'api'
                );
                $this->sendJsonResponse([
                    'success' => false,
                    'error_code' => 'INVALID_ACCESS_KEY',
                    'message' => 'Invalid import access key.',
                ], 401);
            }

            if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
                $this->logUploadFailure('FILE_REQUIRED', 'Multipart file field "file" is required.');
                $this->sendJsonResponse([
                    'success' => false,
                    'error_code' => 'FILE_REQUIRED',
                    'message' => 'Multipart file field "file" is required.',
                ], 400);
            }

            $uploadError = (int) ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);

            if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $this->logUploadFailure('FILE_TOO_LARGE', 'The uploaded CSV file exceeds the allowed size.');
                $this->sendJsonResponse([
                    'success' => false,
                    'error_code' => 'FILE_TOO_LARGE',
                    'message' => 'The uploaded CSV file exceeds the allowed size.',
                ], 413);
            }

            if ($uploadError !== UPLOAD_ERR_OK) {
                $this->logUploadFailure(
                    'UPLOAD_FAILED',
                    'CSV upload failed with error code ' . $uploadError . '.'
                );
                $this->sendJsonResponse([
                    'success' => false,
                    'error_code' => 'UPLOAD_FAILED',
                    'message' => 'CSV upload failed with error code ' . $uploadError . '.',
                ], 400);
            }

            $file = (new ImportFileStorageService())->storeUploadedCsv($_FILES['file'], null);

            (new AuditLogService())->record(
                'file.uploaded',
                'file',
                'success',
                'CSV file uploaded through the API.',
                (string) $file->storedFilename,
                null,
                [
                    'original_filename' => $file->originalFilename,
                    'stored_filename' => $file->storedFilename,
                    'file_size' => $file->fileSize,
                    'file_hash' => $file->fileHash,
                ],
                [],
                'api'
            );

            $this->sendJsonResponse([
                'success' => true,
                'error_code' => null,
                'file' => [
                    'original_filename' => $file->originalFilename,
                    'stored_filename' => $file->storedFilename,
                    'size' => $file->fileSize,
                    'sha256' => $file->fileHash,
                ],
                'message' => 'CSV file uploaded. The import was not started.',
            ], 201);
        } catch (Throwable $exception) {
            $this->logUploadFailure('UPLOAD_FAILED', $exception->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'error_code' => 'UPLOAD_FAILED',
                'message' => $exception->getMessage(),
            ], 400);
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

    private function logUploadFailure(string $errorCode, string $message): void
    {
        $filename = isset($_FILES['file']['name'])
            ? basename((string) $_FILES['file']['name'])
            : null;

        (new AuditLogService())->record(
            'file.upload_failed',
            'file',
            'error',
            $message,
            $filename,
            null,
            null,
            [
                'error_code' => $errorCode,
                'original_filename' => $filename,
            ],
            'api'
        );
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
