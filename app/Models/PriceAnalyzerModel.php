<?php

namespace App\Models;

use App\Enums\Department;
use CodeIgniter\Database\BaseConnection;

class PriceAnalyzerModel extends BaseModel
{
    protected $table = 'sale_items';

    /** @var list<string> */
    public const BUCKET_LABELS = [
        '$0-$10',
        '$10-$20',
        '$20-$30',
        '$30-$40',
        '$40-$50',
        '$50-$60',
        '$60-$80',
        '$80-$100',
        '$100+',
    ];

    /**
     * @param array{warehouse_id?: int} $filters
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     buckets: list<string>,
     *     departments: list<array{key: string, label: string, units: list<int>}>
     * }
     */
    public function getDepartmentBucketUnits(array $filters = [], ?BaseConnection $db = null): array
    {
        $db  = $db ?? $this->db;
        $to  = date('Y-m-d');
        $from = (new \DateTimeImmutable($to))->modify('-1 year')->format('Y-m-d');
        $warehouseId = max(0, (int) ($filters['warehouse_id'] ?? 0));
        if ($warehouseId > 0 && ! $db->fieldExists('warehouse_id', 'sales')) {
            $warehouseId = 0;
        }

        $emptyUnits = array_fill(0, count(self::BUCKET_LABELS), 0);
        $departments = [];

        foreach (Department::cases() as $case) {
            $departments[$case->value] = [
                'key'   => $case->value,
                'label' => $case->label(),
                'units' => $emptyUnits,
            ];
        }

        if (! $db->tableExists('sales') || ! $db->tableExists('sale_items') || ! $db->fieldExists('department', 'products')) {
            return [
                'from'        => $from,
                'to'          => $to,
                'buckets'     => self::BUCKET_LABELS,
                'departments' => array_values($departments),
            ];
        }

        $warehouse = $this->warehouseClause('s', $warehouseId);
        $params = [$from, $to, ...$warehouse['params']];

        $rows = $db->query(
            'SELECT p.department, ' .
            'CASE ' .
            'WHEN si.unit_price < 10 THEN 0 ' .
            'WHEN si.unit_price < 20 THEN 1 ' .
            'WHEN si.unit_price < 30 THEN 2 ' .
            'WHEN si.unit_price < 40 THEN 3 ' .
            'WHEN si.unit_price < 50 THEN 4 ' .
            'WHEN si.unit_price < 60 THEN 5 ' .
            'WHEN si.unit_price < 80 THEN 6 ' .
            'WHEN si.unit_price < 100 THEN 7 ' .
            'ELSE 8 END AS bucket_index, ' .
            'COALESCE(SUM(si.qty), 0) AS units ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE s.sale_date IS NOT NULL ' .
            'AND DATE(s.sale_date) >= ? AND DATE(s.sale_date) <= ?' .
            $warehouse['sql'] . ' ' .
            'GROUP BY p.department, bucket_index',
            $params
        )->getResultArray();

        foreach ($rows as $row) {
            $department = strtolower(trim((string) ($row['department'] ?? '')));
            if ($department === '' || ! Department::isValid($department)) {
                $department = Department::Other->value;
            }

            $bucketIndex = (int) ($row['bucket_index'] ?? -1);
            if ($bucketIndex < 0 || $bucketIndex >= count(self::BUCKET_LABELS)) {
                continue;
            }

            if (! isset($departments[$department])) {
                continue;
            }

            $departments[$department]['units'][$bucketIndex] += (int) ($row['units'] ?? 0);
        }

        return [
            'from'        => $from,
            'to'          => $to,
            'buckets'     => self::BUCKET_LABELS,
            'departments' => array_values($departments),
        ];
    }

    /**
     * @return array{sql: string, params: list<int>}
     */
    private function warehouseClause(string $alias, int $warehouseId): array
    {
        if ($warehouseId <= 0) {
            return ['sql' => '', 'params' => []];
        }

        return [
            'sql'    => " AND {$alias}.warehouse_id = ?",
            'params' => [$warehouseId],
        ];
    }
}
