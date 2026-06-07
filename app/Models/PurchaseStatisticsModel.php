<?php

namespace App\Models;

use App\Enums\Department;
use CodeIgniter\Database\BaseConnection;

class PurchaseStatisticsModel extends BaseModel
{
    protected $table = 'purchases';

    /**
     * @param array{department?: string} $filters
     *
     * @return array{
     *     grouped: array<string, array{
     *         rowspan: int,
     *         total_units: int,
     *         total_cost: float,
     *         departments: array<string, array{
     *             name: string,
     *             rowspan: int,
     *             total_units: int,
     *             total_cost: float,
     *             lines: list<array{supplier_name: string, quantity: int, total_cost: float}>
     *         }>
     *     }>,
     *     year_total: array{total_units: int, total_cost: float}
     * }
     */
    public function getYearlyReport(int $year, array $filters = []): array
    {
        [$startDate, $endDate] = $this->yearBounds($year);
        $db = $this->db;

        if (! $db->tableExists('purchase_items') || ! $db->tableExists('purchases')) {
            return [
                'grouped'    => [],
                'year_total' => ['total_units' => 0, 'total_cost' => 0.0],
            ];
        }

        $filters = $this->normalizeFilters($db, $filters);
        $detailRows = $this->fetchYearlyDetailRows($db, $startDate, $endDate, $filters);

        return [
            'grouped'    => $this->buildGroupedRows($detailRows),
            'year_total' => $this->fetchYearTotal($db, $startDate, $endDate, $filters),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function yearBounds(int $year): array
    {
        $year = max(2000, min(2100, $year));

        return [
            sprintf('%04d-01-01', $year),
            sprintf('%04d-01-01', $year + 1),
        ];
    }

    /**
     * @param array{department?: string} $filters
     *
     * @return array{department: string}
     */
    private function normalizeFilters(BaseConnection $db, array $filters): array
    {
        $department = strtolower(trim((string) ($filters['department'] ?? '')));
        if ($department !== '' && ! Department::isValid($department)) {
            $department = '';
        }

        if ($department !== '' && ! $db->tableExists('products')) {
            $department = '';
        }

        return ['department' => $department];
    }

    private function departmentKeySql(BaseConnection $db): string
    {
        $productDept = $db->fieldExists('department', 'products')
            ? 'NULLIF(TRIM(p.department), \'\')'
            : 'NULL';
        $categoryDept = $db->tableExists('categories') && $db->fieldExists('department', 'categories')
            ? 'NULLIF(TRIM(c.department), \'\')'
            : 'NULL';

        return "COALESCE({$productDept}, {$categoryDept}, 'unspecified')";
    }

    /**
     * @param array{department: string} $filters
     *
     * @return array{sql: string, params: list<string>}
     */
    private function departmentSqlFilter(BaseConnection $db, array $filters): array
    {
        if ($filters['department'] === '') {
            return ['sql' => '', 'params' => []];
        }

        $deptKey = $this->departmentKeySql($db);

        return [
            'sql'    => " AND {$deptKey} = ?",
            'params' => [$filters['department']],
        ];
    }

    /**
     * @param array{department: string} $filters
     *
     * @return list<string>
     */
    private function baseQueryParams(string $startDate, string $endDate, array $filters, BaseConnection $db): array
    {
        $dept = $this->departmentSqlFilter($db, $filters);

        return array_merge([$startDate, $endDate], $dept['params']);
    }

    /**
     * @param array{department: string} $filters
     *
     * @return list<array<string, mixed>>
     */
    private function fetchYearlyDetailRows(BaseConnection $db, string $startDate, string $endDate, array $filters): array
    {
        $deptKey = $this->departmentKeySql($db);
        $departmentFilter = $this->departmentSqlFilter($db, $filters);
        $categoryJoin = $db->tableExists('categories')
            ? 'LEFT JOIN categories c ON c.id = p.category_id'
            : '';

        $sql = <<<SQL
            SELECT
                DATE_FORMAT(pur.purchase_date, '%Y-%m') AS purchase_month,
                {$deptKey} AS department_key,
                pur.supplier_id,
                COALESCE(s.name, 'Unassigned') AS supplier_name,
                SUM(pi.qty) AS quantity,
                SUM(pi.line_total) AS total_cost
            FROM purchase_items pi
            INNER JOIN purchases pur ON pur.id = pi.purchase_id
            INNER JOIN product_variants pv ON pv.id = pi.product_variant_id
            INNER JOIN products p ON p.id = pv.product_id
            {$categoryJoin}
            LEFT JOIN suppliers s ON s.id = pur.supplier_id
            WHERE pur.purchase_date >= ?
              AND pur.purchase_date < ?
              {$departmentFilter['sql']}
            GROUP BY DATE_FORMAT(pur.purchase_date, '%Y-%m'), {$deptKey}, pur.supplier_id, s.name
            ORDER BY purchase_month DESC, department_key ASC, supplier_name ASC
            SQL;

        return $db->query($sql, $this->baseQueryParams($startDate, $endDate, $filters, $db))->getResultArray();
    }

    /**
     * @param array{department: string} $filters
     *
     * @return array{total_units: int, total_cost: float}
     */
    private function fetchYearTotal(BaseConnection $db, string $startDate, string $endDate, array $filters): array
    {
        $departmentFilter = $this->departmentSqlFilter($db, $filters);
        $categoryJoin = $db->tableExists('categories')
            ? 'LEFT JOIN categories c ON c.id = p.category_id'
            : '';
        $deptKey = $this->departmentKeySql($db);

        $joinProducts = $departmentFilter['sql'] !== ''
            ? 'INNER JOIN product_variants pv ON pv.id = pi.product_variant_id
               INNER JOIN products p ON p.id = pv.product_id
               ' . $categoryJoin
            : '';

        $sql = <<<SQL
            SELECT
                COALESCE(SUM(pi.qty), 0) AS total_units,
                COALESCE(SUM(pi.line_total), 0) AS total_cost
            FROM purchase_items pi
            INNER JOIN purchases pur ON pur.id = pi.purchase_id
            {$joinProducts}
            WHERE pur.purchase_date >= ?
              AND pur.purchase_date < ?
              {$departmentFilter['sql']}
            SQL;

        $row = $db->query($sql, $this->baseQueryParams($startDate, $endDate, $filters, $db))->getRowArray();

        return [
            'total_units' => (int) ($row['total_units'] ?? 0),
            'total_cost'  => (float) ($row['total_cost'] ?? 0),
        ];
    }

    /**
     * @param list<array<string, mixed>> $detailRows
     *
     * @return array<string, array{
     *     rowspan: int,
     *     total_units: int,
     *     total_cost: float,
     *     departments: array<string, array{
     *         name: string,
     *         rowspan: int,
     *         total_units: int,
     *         total_cost: float,
     *         lines: list<array{supplier_name: string, quantity: int, total_cost: float}>
     *     }>
     * }>
     */
    private function buildGroupedRows(array $detailRows): array
    {
        $grouped = [];

        foreach ($detailRows as $row) {
            $period = (string) ($row['purchase_month'] ?? '');
            $deptKey = strtolower(trim((string) ($row['department_key'] ?? 'unspecified')));
            if ($deptKey === '') {
                $deptKey = 'unspecified';
            }

            if (! isset($grouped[$period])) {
                $grouped[$period] = [
                    'rowspan'     => 0,
                    'total_units' => 0,
                    'total_cost'  => 0.0,
                    'departments' => [],
                ];
            }

            if (! isset($grouped[$period]['departments'][$deptKey])) {
                $grouped[$period]['departments'][$deptKey] = [
                    'name'        => $this->departmentLabel($deptKey),
                    'rowspan'     => 0,
                    'total_units' => 0,
                    'total_cost'  => 0.0,
                    'lines'       => [],
                ];
            }

            $lineUnits = (int) ($row['quantity'] ?? 0);
            $lineCost  = (float) ($row['total_cost'] ?? 0);

            $grouped[$period]['departments'][$deptKey]['lines'][] = [
                'supplier_name' => (string) ($row['supplier_name'] ?? 'Unassigned'),
                'quantity'      => $lineUnits,
                'total_cost'    => $lineCost,
            ];

            $grouped[$period]['departments'][$deptKey]['total_units'] += $lineUnits;
            $grouped[$period]['departments'][$deptKey]['total_cost'] += $lineCost;
            $grouped[$period]['total_units'] += $lineUnits;
            $grouped[$period]['total_cost'] += $lineCost;
        }

        foreach ($grouped as &$periodGroup) {
            $periodRowspan = 0;

            uasort($periodGroup['departments'], static function (array $a, array $b): int {
                return strcmp($a['name'], $b['name']);
            });

            foreach ($periodGroup['departments'] as &$departmentGroup) {
                $departmentGroup['rowspan'] = count($departmentGroup['lines']);
                $periodRowspan += $departmentGroup['rowspan'];
            }
            unset($departmentGroup);

            $periodGroup['rowspan'] = $periodRowspan;
        }
        unset($periodGroup);

        return $grouped;
    }

    private function departmentLabel(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '' || $value === 'unspecified') {
            return 'Unspecified';
        }

        if (! Department::isValid($value)) {
            return ucfirst($value);
        }

        return Department::from($value)->label();
    }

    public function formatMonthKey(string $monthKey): string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $monthKey . '-01');

        return $dt !== false ? $dt->format('F Y') : $monthKey;
    }
}
