<?php

declare(strict_types=1);

namespace B2B\PriceImport\Repository;

use B2B\PriceImport\Constant\ImportStatus;
use B2B\PriceImport\DTO\ImportCreateData;
use Db;
use DbQuery;

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
            throw new \RuntimeException('Cannot create import record.');
        }

        return (int) Db::getInstance()->Insert_ID();
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
}