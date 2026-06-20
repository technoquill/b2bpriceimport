<?php

declare(strict_types=1);

namespace B2B\PriceImport\Repository;

use B2B\PriceImport\Constant\ImportStatus;
use B2B\PriceImport\Dto\ImportCreateData;
use Db;
use DbQuery;
use RuntimeException;

final class ImportRepository
{
    public function create(ImportCreateData $data): int
    {
        $now = date('Y-m-d H:i:s');

        $result = Db::getInstance()->insert('b2b_import', [
            'name' => pSQL($data->name),
            'source' => pSQL($data->source),
            'original_filename' => $data->originalFilename !== null ? pSQL($data->originalFilename) : null,
            'stored_filename' => $data->storedFilename !== null ? pSQL($data->storedFilename) : null,
            'file_path' => $data->filePath !== null ? pSQL($data->filePath) : null,
            'file_size' => $data->fileSize,
            'file_hash' => $data->fileHash !== null ? pSQL($data->fileHash) : null,
            'status' => ImportStatus::UPLOADED,
            'created_by' => $data->createdBy,
            'date_add' => $now,
            'date_upd' => $now,
        ]);

        if (!$result) {
            throw new RuntimeException('Cannot create import record.');
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function createJob(int $idImport, string $type, int $priority = 5): int
    {
        $now = date('Y-m-d H:i:s');

        $result = Db::getInstance()->insert('b2b_import_job', [
            'id_b2b_import' => $idImport,
            'type' => pSQL($type),
            'status' => 'pending',
            'priority' => $priority,
            'attempts' => 0,
            'max_attempts' => 3,
            'date_add' => $now,
            'date_upd' => $now,
        ]);

        if (!$result) {
            throw new RuntimeException('Cannot create import job.');
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function find(int $idImport): ?array
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('b2b_import');
        $query->where('id_b2b_import = ' . (int) $idImport);

        $row = Db::getInstance()->getRow($query);

        return is_array($row) ? $row : null;
    }

    public function hasActiveImport(): bool
    {
        $statuses = array_map(static fn (string $status): string => "'" . pSQL($status) . "'", ImportStatus::activeStatuses());

        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('b2b_import');
        $query->where('status IN (' . implode(',', $statuses) . ')');

        return (int) Db::getInstance()->getValue($query) > 0;
    }

    public function getLastImports(int $limit = 20): array
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('b2b_import');
        $query->orderBy('id_b2b_import DESC');
        $query->limit($limit);

        $rows = Db::getInstance()->executeS($query);

        return is_array($rows) ? $rows : [];
    }

    public function getImportItems(int $idImport, int $limit = 500): array
    {
        $query = new DbQuery();
        $query->select('
            ii.id_b2b_import_item,
            ii.row_number,
            ii.reference,
            ii.status,
            ii.error_code,
            ii.error_message,
            ii.processed_at,
            ii.date_add,
            ii.date_upd,
            ps.id_product,
            ps.source_price,
            ps.currency_code,
            ps.currency_rate,
            ps.price_uah,
            ps.active,
            ps.validation_status,
            ps.processing_status,
            ps.error_code AS staging_error_code,
            ps.error_message AS staging_error_message
        ');
        $query->from('b2b_import_item', 'ii');
        $query->leftJoin(
            'b2b_import_price_staging',
            'ps',
            'ps.id_b2b_import_item = ii.id_b2b_import_item'
        );
        $query->where('ii.id_b2b_import = ' . (int) $idImport);
        $query->orderBy('ii.row_number ASC, ii.id_b2b_import_item ASC');
        $query->limit($limit);

        $rows = Db::getInstance()->executeS($query);

        return is_array($rows) ? $rows : [];
    }

    public function getImportJobs(int $idImport): array
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('b2b_import_job');
        $query->where('id_b2b_import = ' . (int) $idImport);
        $query->orderBy('id_b2b_import_job ASC');

        $rows = Db::getInstance()->executeS($query);

        return is_array($rows) ? $rows : [];
    }

    public function deleteImport(int $idImport): void
    {
        if ($this->find($idImport) === null) {
            throw new RuntimeException('Import not found.');
        }

        Db::getInstance()->delete('b2b_import_price_staging', 'id_b2b_import = ' . (int) $idImport);
        Db::getInstance()->delete('b2b_import_item', 'id_b2b_import = ' . (int) $idImport);
        Db::getInstance()->delete('b2b_import_job', 'id_b2b_import = ' . (int) $idImport);

        $deleted = Db::getInstance()->delete('b2b_import', 'id_b2b_import = ' . (int) $idImport);

        if (!$deleted) {
            throw new RuntimeException('Cannot delete import record.');
        }
    }

    public function resetRows(int $idImport): void
    {
        Db::getInstance()->delete('b2b_import_price_staging', 'id_b2b_import = ' . (int) $idImport);
        Db::getInstance()->delete('b2b_import_item', 'id_b2b_import = ' . (int) $idImport);

        $this->update($idImport, [
            'header_json' => null,
            'file_offset' => 0,
            'last_row_number' => 0,
            'total_rows' => 0,
            'parsed_rows' => 0,
            'validated_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'warning_rows' => 0,
            'failed_rows' => 0,
            'last_error' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);
    }

    public function addItem(int $idImport, int $rowNumber, ?string $reference, array $payload, string $status, ?string $errorCode = null, ?string $errorMessage = null): int
    {
        $now = date('Y-m-d H:i:s');

        $result = Db::getInstance()->insert('b2b_import_item', [
            'id_b2b_import' => $idImport,
            'row_number' => $rowNumber,
            'reference' => $reference !== null ? pSQL($reference) : null,
            'payload_json' => pSQL(json_encode($payload, JSON_UNESCAPED_UNICODE)),
            'status' => pSQL($status),
            'attempts' => 0,
            'error_code' => $errorCode !== null ? pSQL($errorCode) : null,
            'error_message' => $errorMessage !== null ? pSQL($errorMessage) : null,
            'date_add' => $now,
            'date_upd' => $now,
        ]);

        if (!$result) {
            throw new RuntimeException('Cannot create import item.');
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function addPriceStaging(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data['date_add'] = $now;
        $data['date_upd'] = $now;

        foreach (['reference', 'currency_code', 'validation_status', 'processing_status', 'error_code', 'error_message'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $data[$key] = pSQL((string) $data[$key]);
            }
        }

        $result = Db::getInstance()->insert('b2b_import_price_staging', $data);

        if (!$result) {
            throw new RuntimeException('Cannot create price staging row.');
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function getPendingStagingRows(int $idImport, int $limit = 500): array
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('b2b_import_price_staging');
        $query->where('id_b2b_import = ' . (int) $idImport);
        $query->where("validation_status = 'valid'");
        $query->where("processing_status = 'pending'");
        $query->orderBy('id_b2b_import_price_staging ASC');
        $query->limit($limit);

        $rows = Db::getInstance()->executeS($query);

        return is_array($rows) ? $rows : [];
    }

    public function markRowProcessed(int $idStaging, int $idItem): void
    {
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->update('b2b_import_price_staging', [
            'processing_status' => 'processed',
            'date_upd' => $now,
        ], 'id_b2b_import_price_staging = ' . (int) $idStaging);

        Db::getInstance()->update('b2b_import_item', [
            'status' => 'processed',
            'processed_at' => $now,
            'date_upd' => $now,
        ], 'id_b2b_import_item = ' . (int) $idItem);
    }

    public function markRowFailed(int $idStaging, int $idItem, string $code, string $message): void
    {
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->update('b2b_import_price_staging', [
            'processing_status' => 'failed',
            'error_code' => pSQL($code),
            'error_message' => pSQL($message),
            'date_upd' => $now,
        ], 'id_b2b_import_price_staging = ' . (int) $idStaging);

        Db::getInstance()->update('b2b_import_item', [
            'status' => 'failed',
            'error_code' => pSQL($code),
            'error_message' => pSQL($message),
            'processed_at' => $now,
            'date_upd' => $now,
        ], 'id_b2b_import_item = ' . (int) $idItem);
    }

    public function refreshStats(int $idImport): void
    {
        $stats = Db::getInstance()->getRow('
            SELECT
                COUNT(*) AS total_rows,
                SUM(CASE WHEN status IN ("pending", "processed", "failed") THEN 1 ELSE 0 END) AS parsed_rows,
                SUM(CASE WHEN status IN ("pending", "processed") THEN 1 ELSE 0 END) AS validated_rows,
                SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) AS processed_rows,
                SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) AS success_rows,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) AS failed_rows
            FROM `' . _DB_PREFIX_ . 'b2b_import_item`
            WHERE id_b2b_import = ' . (int) $idImport
        );

        if (!is_array($stats)) {
            return;
        }

        $this->update($idImport, [
            'total_rows' => (int) $stats['total_rows'],
            'parsed_rows' => (int) $stats['parsed_rows'],
            'validated_rows' => (int) $stats['validated_rows'],
            'processed_rows' => (int) $stats['processed_rows'],
            'success_rows' => (int) $stats['success_rows'],
            'failed_rows' => (int) $stats['failed_rows'],
        ]);
    }

    public function setStatus(int $idImport, string $status, ?string $error = null): void
    {
        $data = ['status' => pSQL($status)];

        if (in_array($status, [ImportStatus::PARSING, ImportStatus::PROCESSING], true)) {
            $data['started_at'] = date('Y-m-d H:i:s');
        }

        if (in_array($status, [ImportStatus::FINISHED, ImportStatus::FAILED], true)) {
            $data['finished_at'] = date('Y-m-d H:i:s');
        }

        if ($error !== null) {
            $data['last_error'] = pSQL($error);
        }

        $this->update($idImport, $data);
    }

    public function update(int $idImport, array $data): void
    {
        $data['date_upd'] = date('Y-m-d H:i:s');
        Db::getInstance()->update('b2b_import', $data, 'id_b2b_import = ' . (int) $idImport);
    }
}
