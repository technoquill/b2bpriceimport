<?php

declare(strict_types=1);

namespace B2B\PriceImport\Repository;

use Db;
use DbQuery;
use RuntimeException;

final class AuditLogRepository
{
    private const FILTER_COLUMNS = [
        'action' => 'action',
        'entity_type' => 'entity_type',
        'result' => 'result',
        'channel' => 'channel',
    ];

    public function create(array $entry): int
    {
        $result = Db::getInstance()->insert('b2b_audit_log', [
            'action' => pSQL((string) $entry['action']),
            'entity_type' => pSQL((string) $entry['entity_type']),
            'entity_id' => $this->nullableString($entry['entity_id'] ?? null),
            'result' => pSQL((string) $entry['result']),
            'actor_type' => pSQL((string) ($entry['actor_type'] ?? 'system')),
            'actor_id' => isset($entry['actor_id']) ? (int) $entry['actor_id'] : null,
            'actor_name' => $this->nullableString($entry['actor_name'] ?? null),
            'channel' => pSQL((string) ($entry['channel'] ?? 'system')),
            'message' => pSQL((string) $entry['message'], true),
            'before_json' => $this->encodeJson($entry['before'] ?? null),
            'after_json' => $this->encodeJson($entry['after'] ?? null),
            'context_json' => $this->encodeJson($entry['context'] ?? null),
            'date_add' => (string) ($entry['date_add'] ?? date('Y-m-d H:i:s')),
        ]);

        if (!$result) {
            throw new RuntimeException('Cannot create audit log entry.');
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    public function count(array $filters = []): int
    {
        $query = $this->buildListQuery($filters);
        $query->select('COUNT(*)');

        return (int) Db::getInstance()->getValue($query);
    }

    public function findPage(int $limit, int $offset, array $filters = []): array
    {
        $query = $this->buildListQuery($filters);
        $query->select('*');
        $query->orderBy('id_b2b_audit_log DESC');
        $query->limit(max(1, $limit), max(0, $offset));

        $rows = Db::getInstance()->executeS($query);

        return is_array($rows) ? $rows : [];
    }

    public function getDistinctValues(string $filter): array
    {
        if (!isset(self::FILTER_COLUMNS[$filter])) {
            return [];
        }

        $column = self::FILTER_COLUMNS[$filter];
        $query = new DbQuery();
        $query->select('DISTINCT ' . $column . ' AS filter_value');
        $query->from('b2b_audit_log');
        $query->where($column . " IS NOT NULL AND " . $column . " != ''");
        $query->orderBy($column . ' ASC');

        $rows = Db::getInstance()->executeS($query);

        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            static fn (array $row): string => (string) $row['filter_value'],
            $rows
        ));
    }

    public function deleteOlderThan(int $retentionDays): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }

        $cutoff = date('Y-m-d H:i:s', time() - ($retentionDays * 86400));
        Db::getInstance()->delete(
            'b2b_audit_log',
            "date_add < '" . pSQL($cutoff) . "'"
        );

        return (int) Db::getInstance()->Affected_Rows();
    }

    private function buildListQuery(array $filters): DbQuery
    {
        $query = new DbQuery();
        $query->from('b2b_audit_log');

        foreach (self::FILTER_COLUMNS as $filter => $column) {
            $value = trim((string) ($filters[$filter] ?? ''));

            if ($value !== '') {
                $query->where($column . " = '" . pSQL($value) . "'");
            }
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($this->isDate($dateFrom)) {
            $query->where("date_add >= '" . pSQL($dateFrom . ' 00:00:00') . "'");
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($this->isDate($dateTo)) {
            $query->where("date_add <= '" . pSQL($dateTo . ' 23:59:59') . "'");
        }

        $actor = trim((string) ($filters['actor'] ?? ''));
        if ($actor !== '') {
            $query->where("LOCATE('" . pSQL($actor) . "', COALESCE(actor_name, '')) > 0");
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $escapedSearch = pSQL($search);
            $query->where('(
                LOCATE(\'' . $escapedSearch . '\', action) > 0
                OR LOCATE(\'' . $escapedSearch . '\', COALESCE(entity_id, \'\')) > 0
                OR LOCATE(\'' . $escapedSearch . '\', message) > 0
                OR LOCATE(\'' . $escapedSearch . '\', COALESCE(actor_name, \'\')) > 0
                OR LOCATE(\'' . $escapedSearch . '\', COALESCE(context_json, \'\')) > 0
            )');
        }

        return $query;
    }

    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? pSQL($value, true) : null;
    }

    private function encodeJson($value): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? pSQL($encoded, true) : null;
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
