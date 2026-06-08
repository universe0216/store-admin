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

    private const DAILY_LABEL_COUNT = 366;

    /**
     * @param list<int> $years
     * @param array{warehouse_id?: int, department?: string} $filters
     *
     * @return array{
     *     labels: list<string>,
     *     available_years: list<int>,
     *     series: array<string, array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}>
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
     * @param list<int> $years
     * @param array{warehouse_id?: int, department?: string} $filters
     *
     * @return array{
     *     labels: list<string>,
     *     available_years: list<int>,
     *     series: array<string, array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}>
     * }
     */
    public function getDailyMetrics(array $years, array $filters = []): array
    {
        $db = $this->db;

        if (! $db->tableExists('sales')) {
            return [
                'labels'          => $this->dailyLabels(),
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
            $series[(string) $year] = $this->dailyMetricsForYear($db, $year, $filters);
        }

        return [
            'labels'          => $this->dailyLabels(),
            'available_years' => $this->availableYears($db),
            'series'          => $series,
        ];
    }

    /**
     * @return list<string>
     */
    private function dailyLabels(): array
    {
        static $labels = null;

        if ($labels !== null) {
            return $labels;
        }

        $labels = [];
        $date   = new \DateTimeImmutable('2024-01-01');

        for ($i = 0; $i < self::DAILY_LABEL_COUNT; $i++) {
            $labels[] = $date->modify('+' . $i . ' days')->format('M j');
        }

        return $labels;
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
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
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
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
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
        $unitsRows  = [];
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

            $unitsRows = $db->query(
                'SELECT MONTH(s.sale_date) AS month_num, COALESCE(SUM(si.qty), 0) AS units ' .
                'FROM sale_items si ' .
                'INNER JOIN sales s ON s.id = si.sale_id ' .
                'WHERE ' . $this->saleDateWhere('s') . $itemExtra['sql'] . ' GROUP BY MONTH(s.sale_date)',
                [$start, $end, ...$itemExtra['params']]
            )->getResultArray();
        }

        return $this->assembleMonthlySeries($revenueRows, $orderRows, $profitRows, $unitsRows);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
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

        $unitsRows = $db->query(
            'SELECT MONTH(s.sale_date) AS month_num, COALESCE(SUM(si.qty), 0) AS units ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY MONTH(s.sale_date)',
            $params
        )->getResultArray();

        return $this->assembleMonthlySeries($revenueRows, $orderRows, $profitRows, $unitsRows);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
     */
    private function dailyMetricsForYear(BaseConnection $db, int $year, array $filters): array
    {
        if ($filters['department'] !== '') {
            return $this->dailyMetricsFromSaleItems($db, $year, $filters);
        }

        return $this->dailyMetricsFromSales($db, $year, $filters);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
     */
    private function dailyMetricsFromSales(BaseConnection $db, int $year, array $filters): array
    {
        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-01-01', $year + 1);
        $where = $this->saleDateWhere('sales');
        $extra = $this->warehouseClause('sales', $filters['warehouse_id']);
        $params = [$start, $end, ...$extra['params']];

        $revenueRows = $db->query(
            'SELECT DAYOFYEAR(sale_date) AS day_num, COALESCE(SUM(grand_total), 0) AS revenue ' .
            'FROM sales WHERE ' . $where . $extra['sql'] . ' GROUP BY DAYOFYEAR(sale_date)',
            $params
        )->getResultArray();

        $orderRows = $db->query(
            'SELECT DAYOFYEAR(sale_date) AS day_num, COUNT(*) AS orders ' .
            'FROM sales WHERE ' . $where . $extra['sql'] . ' GROUP BY DAYOFYEAR(sale_date)',
            $params
        )->getResultArray();

        $profitRows = [];
        $unitsRows  = [];
        if ($db->tableExists('sale_items')) {
            $itemExtra = $this->warehouseClause('s', $filters['warehouse_id']);
            $profitRows = $db->query(
                'SELECT DAYOFYEAR(s.sale_date) AS day_num, ' .
                'COALESCE(SUM(si.line_total), 0) - COALESCE(SUM(si.qty * pv.cost_price), 0) AS profit ' .
                'FROM sale_items si ' .
                'INNER JOIN sales s ON s.id = si.sale_id ' .
                'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
                'WHERE ' . $this->saleDateWhere('s') . $itemExtra['sql'] . ' GROUP BY DAYOFYEAR(s.sale_date)',
                [$start, $end, ...$itemExtra['params']]
            )->getResultArray();

            $unitsRows = $db->query(
                'SELECT DAYOFYEAR(s.sale_date) AS day_num, COALESCE(SUM(si.qty), 0) AS units ' .
                'FROM sale_items si ' .
                'INNER JOIN sales s ON s.id = si.sale_id ' .
                'WHERE ' . $this->saleDateWhere('s') . $itemExtra['sql'] . ' GROUP BY DAYOFYEAR(s.sale_date)',
                [$start, $end, ...$itemExtra['params']]
            )->getResultArray();
        }

        return $this->assembleDailySeries($revenueRows, $orderRows, $profitRows, $unitsRows);
    }

    /**
     * @param array{warehouse_id: int, department: string} $filters
     *
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
     */
    private function dailyMetricsFromSaleItems(BaseConnection $db, int $year, array $filters): array
    {
        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-01-01', $year + 1);
        $where = $this->saleDateWhere('s');
        $extra = $this->itemFilterClause($filters);
        $params = [$start, $end, ...$extra['params']];

        $revenueRows = $db->query(
            'SELECT DAYOFYEAR(s.sale_date) AS day_num, COALESCE(SUM(si.line_total), 0) AS revenue ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY DAYOFYEAR(s.sale_date)',
            $params
        )->getResultArray();

        $orderRows = $db->query(
            'SELECT DAYOFYEAR(s.sale_date) AS day_num, COUNT(DISTINCT s.id) AS orders ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY DAYOFYEAR(s.sale_date)',
            $params
        )->getResultArray();

        $profitRows = $db->query(
            'SELECT DAYOFYEAR(s.sale_date) AS day_num, ' .
            'COALESCE(SUM(si.line_total), 0) - COALESCE(SUM(si.qty * pv.cost_price), 0) AS profit ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY DAYOFYEAR(s.sale_date)',
            $params
        )->getResultArray();

        $unitsRows = $db->query(
            'SELECT DAYOFYEAR(s.sale_date) AS day_num, COALESCE(SUM(si.qty), 0) AS units ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $where . $extra['sql'] . ' GROUP BY DAYOFYEAR(s.sale_date)',
            $params
        )->getResultArray();

        return $this->assembleDailySeries($revenueRows, $orderRows, $profitRows, $unitsRows);
    }

    /**
     * @param list<array<string, mixed>> $revenueRows
     * @param list<array<string, mixed>> $orderRows
     * @param list<array<string, mixed>> $profitRows
     * @param list<array<string, mixed>> $unitsRows
     *
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
     */
    private function assembleDailySeries(array $revenueRows, array $orderRows, array $profitRows, array $unitsRows = []): array
    {
        $byDay = [];
        foreach ($revenueRows as $row) {
            $d = (int) ($row['day_num'] ?? 0);
            $byDay[$d]['revenue'] = (float) ($row['revenue'] ?? 0);
        }
        foreach ($orderRows as $row) {
            $d = (int) ($row['day_num'] ?? 0);
            $byDay[$d]['orders'] = (int) ($row['orders'] ?? 0);
        }
        foreach ($profitRows as $row) {
            $d = (int) ($row['day_num'] ?? 0);
            $byDay[$d]['profit'] = (float) ($row['profit'] ?? 0);
        }
        foreach ($unitsRows as $row) {
            $d = (int) ($row['day_num'] ?? 0);
            $byDay[$d]['units'] = (int) ($row['units'] ?? 0);
        }

        $revenue = [];
        $profit  = [];
        $units   = [];
        $orders  = [];

        for ($d = 1; $d <= self::DAILY_LABEL_COUNT; $d++) {
            $revenue[] = $byDay[$d]['revenue'] ?? 0.0;
            $profit[]  = $byDay[$d]['profit'] ?? 0.0;
            $units[]   = $byDay[$d]['units'] ?? 0;
            $orders[]  = $byDay[$d]['orders'] ?? 0;
        }

        return ['revenue' => $revenue, 'profit' => $profit, 'units' => $units, 'orders' => $orders];
    }

    /**
     * @param list<array<string, mixed>> $revenueRows
     * @param list<array<string, mixed>> $orderRows
     * @param list<array<string, mixed>> $profitRows
     * @param list<array<string, mixed>> $unitsRows
     *
     * @return array{revenue: list<float>, profit: list<float>, units: list<int>, orders: list<int>}
     */
    private function assembleMonthlySeries(array $revenueRows, array $orderRows, array $profitRows, array $unitsRows = []): array
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
        foreach ($unitsRows as $row) {
            $m = (int) ($row['month_num'] ?? 0);
            $byMonth[$m]['units'] = (int) ($row['units'] ?? 0);
        }

        $revenue = [];
        $profit  = [];
        $units   = [];
        $orders  = [];

        for ($m = 1; $m <= 12; $m++) {
            $revenue[] = $byMonth[$m]['revenue'] ?? 0.0;
            $profit[]  = $byMonth[$m]['profit'] ?? 0.0;
            $units[]   = $byMonth[$m]['units'] ?? 0;
            $orders[]  = $byMonth[$m]['orders'] ?? 0;
        }

        return ['revenue' => $revenue, 'profit' => $profit, 'units' => $units, 'orders' => $orders];
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
