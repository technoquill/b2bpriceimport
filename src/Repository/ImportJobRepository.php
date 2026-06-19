<?php

declare(strict_types=1);

namespace B2B\PriceImport\Repository;

use B2B\PriceImport\Constant\ImportJobStatus;
use Db;

final class ImportJobRepository
{
    public function create(int $importId, string $type, int $priority = 5): int
    {
        $now = date('Y-m-d H:i:s');

        $result = Db::getInstance()->insert('b2b_import_job', [
            'id_b2b_import' => (int) $importId,
            'type' => pSQL($type),
            'status' => ImportJobStatus::PENDING,
            'priority' => (int) $priority,
            'attempts' => 0,
            'max_attempts' => 3,
            'date_add' => $now,
            'date_upd' => $now,
        ]);

        if (!$result) {
            throw new \RuntimeException('Cannot create import job.');
        }

        return (int) Db::getInstance()->Insert_ID();
    }
}