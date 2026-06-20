<?php

declare(strict_types=1);

namespace B2B\PriceImport\Service;

use Db;
use RuntimeException;

final class ImportLockService
{
    public function acquire(string $lockName, int $ttlSeconds = 120, bool $force = false): bool
    {
        $lockName = trim($lockName);
        $ttlSeconds = max(1, $ttlSeconds);

        if ($lockName === '') {
            throw new RuntimeException('Lock name is required.');
        }

        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);

        if ($force) {
            Db::getInstance()->delete('b2b_import_lock', 'lock_name = "' . pSQL($lockName) . '"');
        } else {
            Db::getInstance()->delete(
                'b2b_import_lock',
                'lock_name = "' . pSQL($lockName) . '" AND expires_at < "' . pSQL($now) . '"'
            );
        }

        return (bool) Db::getInstance()->insert('b2b_import_lock', [
            'lock_name' => pSQL($lockName),
            'locked_at' => $now,
            'expires_at' => $expiresAt,
        ]);
    }

    public function release(string $lockName): void
    {
        $lockName = trim($lockName);

        if ($lockName === '') {
            return;
        }

        Db::getInstance()->delete('b2b_import_lock', 'lock_name = "' . pSQL($lockName) . '"');
    }
}
