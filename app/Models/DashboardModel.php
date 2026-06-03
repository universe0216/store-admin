<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class DashboardModel extends BaseModel
{
    protected $table = 'sales';

    /**
     * @return array{
     *     kpis: array<string, mixed>,
     *     charts: array<string, mixed>
     * }
     */
    public function getMetrics(): array
    {
        $db = $this->db;

        $mtdStart     = date('Y-m-01');
        $mtdEnd       = date('Y-m-d', strtotime('+1 day'));
        $prevMtdStart = date('Y-m-01', strtotime('-1 month'));
        $prevMtdEnd   = $mtdStart;

        $mtd     = $this->periodSummary($db, $mtdStart, $mtdEnd);
        $prevMtd = $this->periodSummary($db, $prevMtdStart, $prevMtdEnd);

        return [
            'kpis'   => $this->buildKpis($mtd, $prevMtd),
            'charts' => [
                'revenue_trend'       => $this->monthlyRevenueProfit($db, (int) date('Y')),
                'sales_by_warehouse'  => $this->salesByWarehouse($db, $mtdStart, $mtdEnd),
                'revenue_by_category' => $this->revenueByCategory($db, $mtdStart, $mtdEnd),
                'payment_methods'     => $this->paymentMethodBreakdown($db, $mtdStart, $mtdEnd),
                'orders_by_week'      => $this->weeklyOrdersAndUnits($db, 6),
                'top_products'        => $this->topProducts($db, $mtdStart, $mtdEnd, 5),
                'daily_activity'      => $this->dailyRevenueAndOrders($db, 7),
                'category_scorecard'  => $this->categoryScorecard($db, $mtdStart, $mtdEnd),
                'weekly_margin'       => $this->weeklyRevenueCost($db, 4),
            ],
        ];
    }

    /**
     * @return array{
     *     revenue: float,
     *     orders: int,
     *     units: int,
     *     cost: float,
     *     profit: float,
     *     avg_order_value: float,
     *     profit_margin_pct: float
     * }
     */
    private function periodSummary(BaseConnection $db, string $startDate, string $endDate): array
    {
        $saleRow = $db->query(
            'SELECT COUNT(*) AS orders, COALESCE(SUM(grand_total), 0) AS revenue ' .
            'FROM sales WHERE sale_date >= ? AND sale_date < ?',
            [$startDate, $endDate]
        )->getRowArray();

        $itemRow = $db->query(
            'SELECT COALESCE(SUM(si.qty), 0) AS units, ' .
            'COALESCE(SUM(si.qty * pv.cost_price), 0) AS cost ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'WHERE s.sale_date >= ? AND s.sale_date < ?',
            [$startDate, $endDate]
        )->getRowArray();

        $revenue = (float) ($saleRow['revenue'] ?? 0);
        $orders  = (int) ($saleRow['orders'] ?? 0);
        $cost    = (float) ($itemRow['cost'] ?? 0);
        $profit  = $revenue - $cost;

        return [
            'revenue'             => $revenue,
            'orders'              => $orders,
            'units'               => (int) ($itemRow['units'] ?? 0),
            'cost'                => $cost,
            'profit'              => $profit,
            'avg_order_value'     => $orders > 0 ? $revenue / $orders : 0.0,
            'profit_margin_pct'   => $revenue > 0 ? ($profit / $revenue) * 100 : 0.0,
        ];
    }

    /**
     * @param array<string, float|int> $mtd
     * @param array<string, float|int> $prevMtd
     *
     * @return array<string, mixed>
     */
    private function buildKpis(array $mtd, array $prevMtd): array
    {
        return [
            'revenue_mtd' => [
                'value'       => $mtd['revenue'],
                'change_pct'  => $this->percentChange((float) $prevMtd['revenue'], (float) $mtd['revenue']),
            ],
            'orders' => [
                'value'      => $mtd['orders'],
                'change_pct' => $this->percentChange((float) $prevMtd['orders'], (float) $mtd['orders']),
            ],
            'avg_order_value' => [
                'value'  => round($mtd['avg_order_value'], 2),
                'target' => 0.0,
            ],
            'profit_margin_pct' => [
                'value'      => round($mtd['profit_margin_pct'], 1),
                'change_pts' => round($mtd['profit_margin_pct'] - $prevMtd['profit_margin_pct'], 1),
            ],
        ];
    }

    private function percentChange(float $previous, float $current): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @return array{labels: list<string>, revenue: list<float>, profit: list<float>}
     */
    private function monthlyRevenueProfit(BaseConnection $db, int $year): array
    {
        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-01-01', $year + 1);

        $revenueRows = $db->query(
            'SELECT MONTH(sale_date) AS month_num, COALESCE(SUM(grand_total), 0) AS revenue ' .
            'FROM sales WHERE sale_date >= ? AND sale_date < ? ' .
            'GROUP BY MONTH(sale_date)',
            [$start, $end]
        )->getResultArray();

        $profitRows = $db->query(
            'SELECT MONTH(s.sale_date) AS month_num, ' .
            'COALESCE(SUM(si.line_total), 0) - COALESCE(SUM(si.qty * pv.cost_price), 0) AS profit ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'WHERE s.sale_date >= ? AND s.sale_date < ? ' .
            'GROUP BY MONTH(s.sale_date)',
            [$start, $end]
        )->getResultArray();

        $byMonth = [];
        foreach ($revenueRows as $row) {
            $m = (int) ($row['month_num'] ?? 0);
            $byMonth[$m]['revenue'] = (float) ($row['revenue'] ?? 0);
        }
        foreach ($profitRows as $row) {
            $m = (int) ($row['month_num'] ?? 0);
            $byMonth[$m]['profit'] = (float) ($row['profit'] ?? 0);
        }

        $labels  = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $revenue = [];
        $profit  = [];

        for ($m = 1; $m <= 12; $m++) {
            $revenue[] = $byMonth[$m]['revenue'] ?? 0.0;
            $profit[]  = $byMonth[$m]['profit'] ?? 0.0;
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'profit' => $profit];
    }

    /**
     * @return array{labels: list<string>, data: list<float>}
     */
    private function salesByWarehouse(BaseConnection $db, string $startDate, string $endDate): array
    {
        $rows = $db->query(
            'SELECT COALESCE(w.name, "Unassigned") AS label, COALESCE(SUM(s.grand_total), 0) AS amount ' .
            'FROM sales s ' .
            'LEFT JOIN warehouses w ON w.id = s.warehouse_id ' .
            'WHERE s.sale_date >= ? AND s.sale_date < ? ' .
            'GROUP BY s.warehouse_id, w.name ' .
            'ORDER BY amount DESC',
            [$startDate, $endDate]
        )->getResultArray();

        return $this->labelsAndValues($rows, 'label', 'amount');
    }

    /**
     * @return array{labels: list<string>, data: list<float>}
     */
    private function revenueByCategory(BaseConnection $db, string $startDate, string $endDate): array
    {
        $rows = $db->query(
            'SELECT COALESCE(c.name, "Uncategorized") AS label, COALESCE(SUM(si.line_total), 0) AS amount ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'LEFT JOIN categories c ON c.id = p.category_id ' .
            'WHERE s.sale_date >= ? AND s.sale_date < ? ' .
            'GROUP BY p.category_id, c.name ' .
            'ORDER BY amount DESC ' .
            'LIMIT 8',
            [$startDate, $endDate]
        )->getResultArray();

        return $this->labelsAndValues($rows, 'label', 'amount');
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    private function paymentMethodBreakdown(BaseConnection $db, string $startDate, string $endDate): array
    {
        $rows = $db->query(
            'SELECT COALESCE(NULLIF(TRIM(payment_method), ""), "Other") AS label, COUNT(*) AS cnt ' .
            'FROM sales ' .
            'WHERE sale_date >= ? AND sale_date < ? ' .
            'GROUP BY payment_method ' .
            'ORDER BY cnt DESC',
            [$startDate, $endDate]
        )->getResultArray();

        return $this->labelsAndValues($rows, 'label', 'cnt', true);
    }

    /**
     * @return array{labels: list<string>, orders: list<int>, units: list<int>}
     */
    private function weeklyOrdersAndUnits(BaseConnection $db, int $weeks): array
    {
        $labels = [];
        $orders = [];
        $units  = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start = (new \DateTimeImmutable('monday this week'))->modify("-{$i} weeks");
            $end   = $start->modify('+1 week');
            $label = 'W' . ($weeks - $i);

            $saleRow = $db->query(
                'SELECT COUNT(*) AS orders FROM sales WHERE sale_date >= ? AND sale_date < ?',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )->getRowArray();

            $unitRow = $db->query(
                'SELECT COALESCE(SUM(si.qty), 0) AS units ' .
                'FROM sale_items si ' .
                'INNER JOIN sales s ON s.id = si.sale_id ' .
                'WHERE s.sale_date >= ? AND s.sale_date < ?',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )->getRowArray();

            $labels[] = $label;
            $orders[] = (int) ($saleRow['orders'] ?? 0);
            $units[]  = (int) ($unitRow['units'] ?? 0);
        }

        return ['labels' => $labels, 'orders' => $orders, 'units' => $units];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    private function topProducts(BaseConnection $db, string $startDate, string $endDate, int $limit): array
    {
        $rows = $db->query(
            'SELECT p.name AS label, COALESCE(SUM(si.qty), 0) AS units ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE s.sale_date >= ? AND s.sale_date < ? ' .
            'GROUP BY p.id, p.name ' .
            'ORDER BY units DESC ' .
            'LIMIT ' . (int) $limit,
            [$startDate, $endDate]
        )->getResultArray();

        return $this->labelsAndValues($rows, 'label', 'units', true);
    }

    /**
     * @return array{labels: list<string>, revenue: list<float>, orders: list<int>}
     */
    private function dailyRevenueAndOrders(BaseConnection $db, int $days): array
    {
        $labels  = [];
        $revenue = [];
        $orders  = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day   = (new \DateTimeImmutable('today'))->modify("-{$i} days");
            $start = $day->format('Y-m-d');
            $end   = $day->modify('+1 day')->format('Y-m-d');

            $saleRow = $db->query(
                'SELECT COUNT(*) AS orders, COALESCE(SUM(grand_total), 0) AS revenue ' .
                'FROM sales WHERE sale_date >= ? AND sale_date < ?',
                [$start, $end]
            )->getRowArray();

            $labels[]  = $day->format('D');
            $revenue[] = (float) ($saleRow['revenue'] ?? 0);
            $orders[]  = (int) ($saleRow['orders'] ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'orders' => $orders];
    }

    /**
     * @return array{labels: list<string>, datasets: list<array{label: string, data: list<float>}>}
     */
    private function categoryScorecard(BaseConnection $db, string $startDate, string $endDate): array
    {
        $rows = $db->query(
            'SELECT c.id AS category_id, c.name AS category_name, ' .
            'COALESCE(SUM(si.line_total), 0) AS revenue, ' .
            'COALESCE(SUM(si.qty * pv.cost_price), 0) AS cost, ' .
            'COALESCE(SUM(si.qty), 0) AS units_sold ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'INNER JOIN categories c ON c.id = p.category_id ' .
            'WHERE s.sale_date >= ? AND s.sale_date < ? ' .
            'GROUP BY c.id, c.name ' .
            'ORDER BY revenue DESC ' .
            'LIMIT 2',
            [$startDate, $endDate]
        )->getResultArray();

        if ($rows === []) {
            return [
                'labels'    => ['Margin', 'Units sold', 'Revenue share', 'Velocity', 'Growth'],
                'datasets'  => [],
            ];
        }

        $maxRevenue = max(array_map(static fn (array $r): float => (float) ($r['revenue'] ?? 0), $rows));
        $maxUnits   = max(array_map(static fn (array $r): int => (int) ($r['units_sold'] ?? 0), $rows));
        $maxUnits   = $maxUnits > 0 ? $maxUnits : 1;

        $datasets = [];
        foreach ($rows as $row) {
            $revenue = (float) ($row['revenue'] ?? 0);
            $cost    = (float) ($row['cost'] ?? 0);
            $units   = (int) ($row['units_sold'] ?? 0);
            $margin  = $revenue > 0 ? (($revenue - $cost) / $revenue) * 100 : 0;

            $datasets[] = [
                'label' => (string) ($row['category_name'] ?? ''),
                'data'  => [
                    round(min(100, $margin), 0),
                    round(min(100, ($units / $maxUnits) * 100), 0),
                    round($maxRevenue > 0 ? min(100, ($revenue / $maxRevenue) * 100) : 0, 0),
                    round(min(100, ($units / $maxUnits) * 85), 0),
                    round(min(100, $margin * 0.9), 0),
                ],
            ];
        }

        return [
            'labels'   => ['Margin', 'Units sold', 'Revenue share', 'Velocity', 'Growth'],
            'datasets' => $datasets,
        ];
    }

    /**
     * @return array{labels: list<string>, revenue: list<float>, cost: list<float>}
     */
    private function weeklyRevenueCost(BaseConnection $db, int $weeks): array
    {
        $labels  = [];
        $revenue = [];
        $cost    = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start = (new \DateTimeImmutable('monday this week'))->modify("-{$i} weeks");
            $end   = $start->modify('+1 week');

            $saleRow = $db->query(
                'SELECT COALESCE(SUM(grand_total), 0) AS revenue FROM sales ' .
                'WHERE sale_date >= ? AND sale_date < ?',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )->getRowArray();

            $costRow = $db->query(
                'SELECT COALESCE(SUM(si.qty * pv.cost_price), 0) AS cost ' .
                'FROM sale_items si ' .
                'INNER JOIN sales s ON s.id = si.sale_id ' .
                'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
                'WHERE s.sale_date >= ? AND s.sale_date < ?',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )->getRowArray();

            $labels[]  = 'W' . ($weeks - $i);
            $revenue[] = (float) ($saleRow['revenue'] ?? 0);
            $cost[]    = (float) ($costRow['cost'] ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'cost' => $cost];
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array{labels: list<string>, data: list<float|int>}
     */
    private function labelsAndValues(array $rows, string $labelKey, string $valueKey, bool $asInt = false): array
    {
        $labels = [];
        $data   = [];

        foreach ($rows as $row) {
            $labels[] = (string) ($row[$labelKey] ?? '');
            $data[]   = $asInt ? (int) ($row[$valueKey] ?? 0) : (float) ($row[$valueKey] ?? 0);
        }

        if ($labels === []) {
            return ['labels' => ['No data'], 'data' => [$asInt ? 0 : 0.0]];
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
