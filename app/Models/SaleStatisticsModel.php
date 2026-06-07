<?php

namespace App\Models;

use App\Enums\Department;
use CodeIgniter\Database\BaseConnection;

class SaleStatisticsModel extends BaseModel
{
    protected $table = 'sales';

    /**
     * @return array{
     *     grouped: array<string, array{rowspan: int, total_income: float, profit: float, orders: int, quantity: int, warehouses: array<int, array{name: string, rowspan: int, total_income: float, profit: float, orders: int, quantity: int, lines: list<array<string, mixed>>}>}>,
     *     month_total: array{total_income: float, profit: float, quantity: int},
     *     warehouse_totals: list<array{warehouse_id: int|null, warehouse_name: string, total_income: float, profit: float, quantity: int}>
     * }
     */
    /**
     * @param array{warehouse_id?: int, department?: string} $filters
     */
    public function getMonthlyReport(string $month, array $filters = []): array
    {
        [$startDate, $endDate] = $this->monthBounds($month);
        $db = $this->db;
        $filters = $this->normalizeFilters($db, $filters);

        $detailRows = $this->fetchDetailRows($db, $startDate, $endDate, $filters);
        $rollupRows = $this->fetchRollupRows($db, $startDate, $endDate, $filters);

        $grouped = $this->buildGroupedRows($detailRows, 'sale_date');
        $this->applyOrderCounts($grouped, $this->fetchOrderCountRows($db, $startDate, $endDate, $filters), 'sale_date');

        return [
            'grouped'          => $grouped,
            'month_total'      => $this->extractPeriodTotal($rollupRows),
            'warehouse_totals' => $this->extractWarehouseTotals($rollupRows),
        ];
    }

    /**
     * @return array{
     *     grouped: array<string, array{rowspan: int, total_income: float, profit: float, orders: int, quantity: int, warehouses: array<int, array{name: string, rowspan: int, total_income: float, profit: float, orders: int, quantity: int, lines: list<array<string, mixed>>}>}>,
     *     year_total: array{total_income: float, profit: float, quantity: int},
     *     warehouse_totals: list<array{warehouse_id: int|null, warehouse_name: string, total_income: float, profit: float, quantity: int}>
     * }
     */
    /**
     * @param array{warehouse_id?: int, department?: string} $filters
     */
    public function getYearlyReport(int $year, array $filters = []): array
    {
        [$startDate, $endDate] = $this->yearBounds($year);
        $db = $this->db;
        $filters = $this->normalizeFilters($db, $filters);

        $detailRows = $this->fetchYearlyDetailRows($db, $startDate, $endDate, $filters);
        $rollupRows = $this->fetchRollupRows($db, $startDate, $endDate, $filters);

        $grouped = $this->buildGroupedRows($detailRows, 'sale_month');
        $this->applyOrderCounts($grouped, $this->fetchYearlyOrderCountRows($db, $startDate, $endDate, $filters), 'sale_month');

        return [
            'grouped'          => $grouped,
            'year_total'       => $this->extractPeriodTotal($rollupRows),
            'warehouse_totals' => $this->extractWarehouseTotals($rollupRows),
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
     * @return array{0: string, 1: string}
     */
    private function monthBounds(string $month): array
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');
        if ($start === false) {
            $start = new \DateTimeImmutable(date('Y-m-01'));
        }

        $end = $start->modify('+1 month');

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{warehouse_id: int, department: string}
     */
    private function normalizeFilters(BaseConnection $db, array $filters): array
    {
        $warehouseId = max(0, (int) ($filters['warehouse_id'] ?? 0));
        if ($warehouseId > 0 && ! $db->fieldExists('warehouse_id', 'sales')) {
            $warehouseId = 0;
        }

        $department = strtolower(trim((string) ($filters['department'] ?? '')));
        if ($department !== '' && ! Department::isValid($department)) {
            $department = '';
        }

        if ($department !== '' && (! $db->tableExists('sale_items') || ! $db->fieldExists('department', 'products'))) {
            $department = '';
        }

        return [
            'warehouse_id' => $warehouseId,
            'department'   => $department,
        ];
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{sql: string, params: list<int|string>}
     */
    private function departmentSqlFilter(array $filters): array
    {
        if ($filters['department'] === '') {
            return ['sql' => '', 'params' => []];
        }

        return [
            'sql'    => ' AND p.department = ?',
            'params' => [$filters['department']],
        ];
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return list<int|string>
     */
    private function baseQueryParams(string $startDate, string $endDate, array $filters): array
    {
        $params = [$startDate, $endDate];
        $dept = $this->departmentSqlFilter($filters);

        if ($filters['warehouse_id'] > 0) {
            $params[] = $filters['warehouse_id'];
        }

        return array_merge($params, $dept['params']);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     */
    private function fetchDetailRows(BaseConnection $db, string $startDate, string $endDate, array $filters): array
    {
        $warehouseFilter = $this->warehouseSqlFilter($filters['warehouse_id']);
        $departmentFilter = $this->departmentSqlFilter($filters);

        $sql = <<<SQL
            SELECT
                DATE(s.sale_date) AS sale_date,
                s.warehouse_id,
                COALESCE(w.name, 'Unassigned') AS warehouse_name,
                p.id AS product_id,
                p.name AS product_name,
                COALESCE(NULLIF(TRIM(pv.style), ''), '—') AS product_style,
                GROUP_CONCAT(DISTINCT pv.size ORDER BY CAST(pv.size AS UNSIGNED), pv.size SEPARATOR ', ') AS sizes,
                SUM(si.qty) AS quantity,
                SUM(si.line_total) AS total_income,
                SUM(si.qty * pv.cost_price) AS total_cost,
                SUM(si.line_total) - SUM(si.qty * pv.cost_price) AS profit
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            INNER JOIN product_variants pv ON pv.id = si.product_variant_id
            INNER JOIN products p ON p.id = pv.product_id
            LEFT JOIN warehouses w ON w.id = s.warehouse_id
            WHERE s.sale_date >= ?
              AND s.sale_date < ?
              {$warehouseFilter}{$departmentFilter['sql']}
            GROUP BY DATE(s.sale_date), s.warehouse_id, w.name, p.id, p.name, pv.style
            ORDER BY sale_date DESC, warehouse_name ASC, product_name ASC, product_style ASC
            SQL;

        return $db->query($sql, $this->baseQueryParams($startDate, $endDate, $filters))->getResultArray();
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return list<array<string, mixed>>
     */
    private function fetchYearlyDetailRows(BaseConnection $db, string $startDate, string $endDate, array $filters): array
    {
        $warehouseFilter = $this->warehouseSqlFilter($filters['warehouse_id']);
        $departmentFilter = $this->departmentSqlFilter($filters);

        $sql = <<<SQL
            SELECT
                DATE_FORMAT(s.sale_date, '%Y-%m') AS sale_month,
                s.warehouse_id,
                COALESCE(w.name, 'Unassigned') AS warehouse_name,
                p.id AS product_id,
                p.name AS product_name,
                COALESCE(NULLIF(TRIM(pv.style), ''), '—') AS product_style,
                GROUP_CONCAT(DISTINCT pv.size ORDER BY CAST(pv.size AS UNSIGNED), pv.size SEPARATOR ', ') AS sizes,
                SUM(si.qty) AS quantity,
                SUM(si.line_total) AS total_income,
                SUM(si.qty * pv.cost_price) AS total_cost,
                SUM(si.line_total) - SUM(si.qty * pv.cost_price) AS profit
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            INNER JOIN product_variants pv ON pv.id = si.product_variant_id
            INNER JOIN products p ON p.id = pv.product_id
            LEFT JOIN warehouses w ON w.id = s.warehouse_id
            WHERE s.sale_date >= ?
              AND s.sale_date < ?
              {$warehouseFilter}{$departmentFilter['sql']}
            GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m'), s.warehouse_id, w.name, p.id, p.name, pv.style
            ORDER BY sale_month DESC, warehouse_name ASC, product_name ASC, product_style ASC
            SQL;

        return $db->query($sql, $this->baseQueryParams($startDate, $endDate, $filters))->getResultArray();
    }

    private function warehouseSqlFilter(int $warehouseId): string
    {
        return $warehouseId > 0
            ? ' AND s.warehouse_id = ?'
            : '';
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return list<array<string, mixed>>
     */
    private function fetchRollupRows(BaseConnection $db, string $startDate, string $endDate, array $filters): array
    {
        $warehouseFilter = $this->warehouseSqlFilter($filters['warehouse_id']);
        $departmentFilter = $this->departmentSqlFilter($filters);

        $sql = <<<SQL
            SELECT
                s.warehouse_id,
                COALESCE(MAX(w.name), 'Unassigned') AS warehouse_name,
                SUM(si.qty) AS quantity,
                SUM(si.line_total) AS total_income,
                SUM(si.qty * pv.cost_price) AS total_cost,
                SUM(si.line_total) - SUM(si.qty * pv.cost_price) AS profit
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            INNER JOIN product_variants pv ON pv.id = si.product_variant_id
            INNER JOIN products p ON p.id = pv.product_id
            LEFT JOIN warehouses w ON w.id = s.warehouse_id
            WHERE s.sale_date >= ?
              AND s.sale_date < ?
              {$warehouseFilter}{$departmentFilter['sql']}
            GROUP BY s.warehouse_id WITH ROLLUP
            SQL;

        return $db->query($sql, $this->baseQueryParams($startDate, $endDate, $filters))->getResultArray();
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return list<array{sale_date: string, warehouse_id: int, orders: int}>
     */
    private function fetchOrderCountRows(BaseConnection $db, string $startDate, string $endDate, array $filters): array
    {
        return $this->fetchPeriodOrderCountRows(
            $db,
            $startDate,
            $endDate,
            $filters,
            'DATE(s.sale_date)',
            'sale_date',
            'DATE(s.sale_date), s.warehouse_id'
        );
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return list<array{sale_month: string, warehouse_id: int, orders: int}>
     */
    private function fetchYearlyOrderCountRows(BaseConnection $db, string $startDate, string $endDate, array $filters): array
    {
        return $this->fetchPeriodOrderCountRows(
            $db,
            $startDate,
            $endDate,
            $filters,
            "DATE_FORMAT(s.sale_date, '%Y-%m')",
            'sale_month',
            "DATE_FORMAT(s.sale_date, '%Y-%m'), s.warehouse_id"
        );
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return list<array<string, mixed>>
     */
    private function fetchPeriodOrderCountRows(
        BaseConnection $db,
        string $startDate,
        string $endDate,
        array $filters,
        string $periodExpr,
        string $periodAlias,
        string $groupBy
    ): array {
        $warehouseFilter = $this->warehouseSqlFilter($filters['warehouse_id']);
        $departmentFilter = $this->departmentSqlFilter($filters);

        if ($filters['department'] !== '') {
            $sql = <<<SQL
                SELECT
                    {$periodExpr} AS {$periodAlias},
                    s.warehouse_id,
                    COUNT(DISTINCT s.id) AS orders
                FROM sales s
                INNER JOIN sale_items si ON si.sale_id = s.id
                INNER JOIN product_variants pv ON pv.id = si.product_variant_id
                INNER JOIN products p ON p.id = pv.product_id
                WHERE s.sale_date >= ?
                  AND s.sale_date < ?
                  {$warehouseFilter}{$departmentFilter['sql']}
                GROUP BY {$groupBy}
                SQL;
        } else {
            $sql = <<<SQL
                SELECT
                    {$periodExpr} AS {$periodAlias},
                    s.warehouse_id,
                    COUNT(DISTINCT s.id) AS orders
                FROM sales s
                WHERE s.sale_date >= ?
                  AND s.sale_date < ?
                  {$warehouseFilter}
                GROUP BY {$groupBy}
                SQL;
        }

        return $db->query($sql, $this->baseQueryParams($startDate, $endDate, $filters))->getResultArray();
    }

    /**
     * @param array<string, array{rowspan: int, total_income: float, profit: float, orders: int, quantity: int, warehouses: array<int, array{name: string, rowspan: int, total_income: float, profit: float, orders: int, quantity: int, lines: list<array<string, mixed>>}>}> $grouped
     * @param list<array<string, mixed>> $orderRows
     */
    private function applyOrderCounts(array &$grouped, array $orderRows, string $periodField): void
    {
        foreach ($orderRows as $row) {
            $period = (string) ($row[$periodField] ?? '');
            $warehouseId = (int) ($row['warehouse_id'] ?? 0);
            $orders = (int) ($row['orders'] ?? 0);

            if ($period === '' || ! isset($grouped[$period]['warehouses'][$warehouseId])) {
                continue;
            }

            $grouped[$period]['warehouses'][$warehouseId]['orders'] = $orders;
            $grouped[$period]['orders'] = (int) ($grouped[$period]['orders'] ?? 0) + $orders;
        }
    }

    /**
     * @param list<array<string, mixed>> $detailRows
     *
     * @return array<string, array{rowspan: int, total_income: float, profit: float, orders: int, quantity: int, warehouses: array<int, array{name: string, rowspan: int, total_income: float, profit: float, orders: int, quantity: int, lines: list<array<string, mixed>>}>}>
     */
    private function buildGroupedRows(array $detailRows, string $periodField): array
    {
        $grouped = [];

        foreach ($detailRows as $row) {
            $period = (string) ($row[$periodField] ?? '');
            $warehouseId = (int) ($row['warehouse_id'] ?? 0);

            if (! isset($grouped[$period])) {
                $grouped[$period] = [
                    'rowspan'      => 0,
                    'total_income' => 0.0,
                    'profit'       => 0.0,
                    'orders'       => 0,
                    'quantity'     => 0,
                    'warehouses'   => [],
                ];
            }

            if (! isset($grouped[$period]['warehouses'][$warehouseId])) {
                $grouped[$period]['warehouses'][$warehouseId] = [
                    'name'         => (string) ($row['warehouse_name'] ?? 'Unassigned'),
                    'rowspan'      => 0,
                    'total_income' => 0.0,
                    'profit'       => 0.0,
                    'orders'       => 0,
                    'quantity'     => 0,
                    'lines'        => [],
                ];
            }

            $lineIncome = (float) ($row['total_income'] ?? 0);
            $lineProfit = (float) ($row['profit'] ?? 0);
            $lineQty = (int) ($row['quantity'] ?? 0);

            $grouped[$period]['warehouses'][$warehouseId]['lines'][] = [
                'product_name'  => (string) ($row['product_name'] ?? ''),
                'product_style' => (string) ($row['product_style'] ?? ''),
                'sizes'         => (string) ($row['sizes'] ?? ''),
                'quantity'      => (int) ($row['quantity'] ?? 0),
                'total_income'  => $lineIncome,
                'profit'        => $lineProfit,
            ];

            $grouped[$period]['warehouses'][$warehouseId]['total_income'] += $lineIncome;
            $grouped[$period]['warehouses'][$warehouseId]['profit'] += $lineProfit;
            $grouped[$period]['warehouses'][$warehouseId]['quantity'] += $lineQty;
            $grouped[$period]['total_income'] += $lineIncome;
            $grouped[$period]['profit'] += $lineProfit;
            $grouped[$period]['quantity'] += $lineQty;
        }

        foreach ($grouped as &$periodGroup) {
            $periodRowspan = 0;

            foreach ($periodGroup['warehouses'] as &$warehouseGroup) {
                $warehouseGroup['rowspan'] = count($warehouseGroup['lines']);
                $periodRowspan += $warehouseGroup['rowspan'];
            }
            unset($warehouseGroup);

            $periodGroup['rowspan'] = $periodRowspan;
        }
        unset($periodGroup);

        return $grouped;
    }

    public function formatMonthKey(string $monthKey): string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $monthKey . '-01');

        return $dt !== false ? $dt->format('F Y') : $monthKey;
    }

    /**
     * @param list<array<string, mixed>> $rollupRows
     *
     * @return array{total_income: float, profit: float, quantity: int}
     */
    private function extractPeriodTotal(array $rollupRows): array
    {
        foreach ($rollupRows as $row) {
            if ($row['warehouse_id'] === null) {
                return [
                    'total_income' => (float) ($row['total_income'] ?? 0),
                    'profit'       => (float) ($row['profit'] ?? 0),
                    'quantity'     => (int) ($row['quantity'] ?? 0),
                ];
            }
        }

        return ['total_income' => 0.0, 'profit' => 0.0, 'quantity' => 0];
    }

    /**
     * @param list<array<string, mixed>> $rollupRows
     *
     * @return list<array{warehouse_id: int|null, warehouse_name: string, total_income: float, profit: float, quantity: int}>
     */
    private function extractWarehouseTotals(array $rollupRows): array
    {
        $totals = [];

        foreach ($rollupRows as $row) {
            if ($row['warehouse_id'] === null) {
                continue;
            }

            $totals[] = [
                'warehouse_id'   => $row['warehouse_id'] !== null ? (int) $row['warehouse_id'] : null,
                'warehouse_name' => (string) ($row['warehouse_name'] ?? 'Unassigned'),
                'total_income'   => (float) ($row['total_income'] ?? 0),
                'profit'         => (float) ($row['profit'] ?? 0),
                'quantity'       => (int) ($row['quantity'] ?? 0),
            ];
        }

        usort($totals, static fn (array $a, array $b): int => strcmp($a['warehouse_name'], $b['warehouse_name']));

        return $totals;
    }
}
