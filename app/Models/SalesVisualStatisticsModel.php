<?php

namespace App\Models;

use App\Enums\Department;
use CodeIgniter\Database\BaseConnection;

class SalesVisualStatisticsModel extends BaseModel
{
    protected $table = 'sales';

    /** @var list<string> */
    private const MONTH_LABELS = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];

    /**
     * @param list<int> $years
     * @param array{warehouse_id?: int, department?: string} $filters
     *
     * @return array{
     *     labels: list<string>,
     *     available_years: list<int>,
     *     series: array<string, array{revenue: list<float>, profit: list<float>, orders: list<int>}>
     * }
     */
    public function getMonthlyMetrics(array $years, array $filters = []): array
    {
        $db = $this->db;

        if (! $db->tableExists('sales')) {
            return [
                'labels'          => self::MONTH_LABELS,
                'available_years' => [],
                'series'          => [],
            ];
        }

        $filters = $this->normalizeFilters($db, $filters);
        $years   = $this->normalizeYears($years);
        if ($years === []) {
            $years = [(int) date('Y')];
        }

        $series = [];
        foreach ($years as $year) {
            $series[(string) $year] = $this->monthlyMetricsForYear($db, $year, $filters);
        }

        return [
            'labels'          => self::MONTH_LABELS,
            'available_years' => $this->availableYears($db),
            'series'          => $series,
        ];
    }

    /**
     * @param array{warehouse_id?: int, department?: string} $filters
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
     * @return list<int>
     */
    public function availableYears(?BaseConnection $db = null): array
    {
        $db = $db ?? $this->db;

        if (! $db->tableExists('sales')) {
            return [(int) date('Y')];
        }

        $rows = $db->query(
            'SELECT DISTINCT YEAR(sale_date) AS year_num FROM sales ' .
            'WHERE sale_date IS NOT NULL ORDER BY year_num DESC'
        )->getResultArray();

        $years = [];
        foreach ($rows as $row) {
            $y = (int) ($row['year_num'] ?? 0);
            if ($y >= 2000 && $y <= 2100) {
                $years[] = $y;
            }
        }

        if ($years === []) {
            $years[] = (int) date('Y');
        }

        return $years;
    }

    /**
     * @param list<int|string> $years
     *
     * @return list<int>
     */
    private function normalizeYears(array $years): array
    {
        $normalized = [];
        foreach ($years as $year) {
            $y = (int) $year;
            if ($y >= 2000 && $y <= 2100) {
                $normalized[$y] = $y;
            }
        }

        ksort($normalized);

        return array_values($normalized);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{revenue: list<float>, profit: list<float>, orders: list<int>}
     */
    private function monthlyMetricsForYear(BaseConnection $db, int $year, array $filters): array
    {
        if ($filters['department'] !== '') {
            return $this->monthlyMetricsFromSaleItems($db, $year, $filters);
        }

        return $this->monthlyMetricsFromSales($db, $year, $filters);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{revenue: list<float>, profit: list<float>, orders: list<int>}
     */
    private function monthlyMetricsFromSales(BaseConnection $db, int $year, array $filters): array
    {
        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-01-01', $year + 1);
        $where = $this->saleDateWhere('sales');
        $extra = $this->warehouseClause('sales', $filters['warehouse_id']);
        $params = [$start, $end, ...$extra['params']];

        $revenueRows = $db->query(
            'SELECT MONTH(sale_date) AS month_num, COALESCE(SUM(grand_total), 0) AS revenue ' .
            'FROM sales WHERE ' . $where . $extra['sql'] . ' GROUP BY MONTH(sale_date)',
            $params
        )->getResultArray();

        $orderRows = $db->query(
            'SELECT MONTH(sale_date) AS month_num, COUNT(*) AS orders ' .
            'FROM sales WHERE ' . $where . $extra['sql'] . ' GROUP BY MONTH(sale_date)',
            $params
        )->getResultArray();

        $profitRows = [];
        if ($db->tableExists('sale_items')) {
            $itemExtra = $this->warehouseClause('s', $filters['warehouse_id']);
            $profitRows = $db->query(
                'SELECT MONTH(s.sale_date) AS month_num, ' .
                'COALESCE(SUM(si.line_total), 0) - COALESCE(SUM(si.qty * pv.cost_price), 0) AS profit ' .
                'FROM sale_items si ' .
                'INNER JOIN sales s ON s.id = si.sale_id ' .
                'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
                'WHERE ' . $this->saleDateWhere('s') . $itemExtra['sql'] . ' GROUP BY MONTH(s.sale_date)',
                [$start, $end, ...$itemExtra['params']]
            )->getResultArray();
        }

        return $this->assembleMonthlySeries($revenueRows, $orderRows, $profitRows);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{revenue: list<float>, profit: list<float>, orders: list<int>}
     */
    private function monthlyMetricsFromSaleItems(BaseConnection $db, int $year, array $filters): array
    {
        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-01-01', $year + 1);
        $where = $this->saleDateWhere('s');
        $extra = $this->itemFilterClause($filters);
        $params = [$start, $end, ...$extra['params']];

        $revenueRows = $db->query(
            'SELECT MONTH(s.sale_date) AS month_num, COALESCE(SUM(si.line_total), 0) AS revenue ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY MONTH(s.sale_date)',
            $params
        )->getResultArray();

        $orderRows = $db->query(
            'SELECT MONTH(s.sale_date) AS month_num, COUNT(DISTINCT s.id) AS orders ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY MONTH(s.sale_date)',
            $params
        )->getResultArray();

        $profitRows = $db->query(
            'SELECT MONTH(s.sale_date) AS month_num, ' .
            'COALESCE(SUM(si.line_total), 0) - COALESCE(SUM(si.qty * pv.cost_price), 0) AS profit ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY MONTH(s.sale_date)',
            $params
        )->getResultArray();

        return $this->assembleMonthlySeries($revenueRows, $orderRows, $profitRows);
    }

    /**
     * @param list<array<string, mixed>> $revenueRows
     * @param list<array<string, mixed>> $orderRows
     * @param list<array<string, mixed>> $profitRows
     *
     * @return array{revenue: list<float>, profit: list<float>, orders: list<int>}
     */
    private function assembleMonthlySeries(array $revenueRows, array $orderRows, array $profitRows): array
    {
        $byMonth = [];
        foreach ($revenueRows as $row) {
            $m = (int) ($row['month_num'] ?? 0);
            $byMonth[$m]['revenue'] = (float) ($row['revenue'] ?? 0);
        }
        foreach ($orderRows as $row) {
            $m = (int) ($row['month_num'] ?? 0);
            $byMonth[$m]['orders'] = (int) ($row['orders'] ?? 0);
        }
        foreach ($profitRows as $row) {
            $m = (int) ($row['month_num'] ?? 0);
            $byMonth[$m]['profit'] = (float) ($row['profit'] ?? 0);
        }

        $revenue = [];
        $profit  = [];
        $orders  = [];

        for ($m = 1; $m <= 12; $m++) {
            $revenue[] = $byMonth[$m]['revenue'] ?? 0.0;
            $profit[]  = $byMonth[$m]['profit'] ?? 0.0;
            $orders[]  = $byMonth[$m]['orders'] ?? 0;
        }

        return ['revenue' => $revenue, 'profit' => $profit, 'orders' => $orders];
    }

    /**
     * @return array{sql: string, params: list<int|string>}
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

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{sql: string, params: list<int|string>}
     */
    private function itemFilterClause(array $filters): array
    {
        $sql    = ' AND p.department = ?';
        $params = [$filters['department']];

        $warehouse = $this->warehouseClause('s', $filters['warehouse_id']);
        $sql .= $warehouse['sql'];
        $params = array_merge($params, $warehouse['params']);

        return ['sql' => $sql, 'params' => $params];
    }

    private function saleDateWhere(string $alias): string
    {
        return "DATE({$alias}.sale_date) >= ? AND DATE({$alias}.sale_date) < ?";
    }
}
