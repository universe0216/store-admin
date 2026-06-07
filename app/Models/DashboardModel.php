<?php

namespace App\Models;

use App\Enums\Department;
use App\Enums\Gender;
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

        if (! $db->tableExists('sales')) {
            return $this->emptyMetrics();
        }

        $mtdStart     = date('Y-m-01');
        $mtdEnd       = date('Y-m-d', strtotime('+1 day'));
        $prevMtdStart = date('Y-m-01', strtotime('-1 month'));
        $prevMtdEnd   = $mtdStart;
        $yoyMtdStart  = date('Y-m-01', strtotime('-1 year'));
        $yoyMtdEnd    = date('Y-m-d', strtotime($mtdEnd . ' -1 year'));

        $mtd     = $this->periodSummary($db, $mtdStart, $mtdEnd);
        $prevMtd = $this->periodSummary($db, $prevMtdStart, $prevMtdEnd);
        $yoyMtd  = $this->periodSummary($db, $yoyMtdStart, $yoyMtdEnd);

        return [
            'kpis'   => $this->buildKpis($mtd, $prevMtd, $yoyMtd, $this->inventorySummary($db)),
            'charts' => [
                'revenue_trend'       => $this->monthlyRevenueProfit($db, (int) date('Y')),
                'sales_by_warehouse'  => $this->salesByWarehouse($db, $mtdStart, $mtdEnd),
                'revenue_by_category' => $this->revenueByCategory($db, $mtdStart, $mtdEnd),
                'sales_by_gender'     => $this->salesByGender($db, $mtdStart, $mtdEnd),
                'payment_methods'     => $this->paymentMethodBreakdown($db, $mtdStart, $mtdEnd),
                'orders_by_week'      => $this->weeklyOrdersAndUnits($db, 6),
                'top_products'        => $this->topProducts($db, $mtdStart, $mtdEnd, 5),
                'daily_activity'      => $this->dailyRevenueAndOrders($db, 7),
                'category_scorecard'  => $this->categoryScorecard($db, $mtdStart, $mtdEnd),
                'weekly_margin'       => $this->weeklyRevenueCost($db, 4),
                'department_metrics'  => $this->departmentMetrics($db),
            ],
        ];
    }

    /**
     * @return array{kpis: array<string, mixed>, charts: array<string, mixed>}
     */
    private function emptyMetrics(): array
    {
        $emptyPeriod = [
            'revenue'           => 0.0,
            'orders'            => 0,
            'units'             => 0,
            'cost'              => 0.0,
            'profit'            => 0.0,
            'avg_order_value'   => 0.0,
            'profit_margin_pct' => 0.0,
        ];

        $emptyInventory = [
            'remaining_units'  => 0,
            'inventory_value'  => 0.0,
            'returned_pct'     => 0.0,
        ];

        return [
            'kpis'   => $this->buildKpis($emptyPeriod, $emptyPeriod, $emptyPeriod, $emptyInventory),
            'charts' => [
                'revenue_trend'       => ['labels' => [], 'revenue' => [], 'profit' => []],
                'sales_by_warehouse'  => ['labels' => [], 'data' => []],
                'revenue_by_category' => ['labels' => [], 'data' => []],
                'sales_by_gender'     => ['labels' => [], 'data' => []],
                'payment_methods'     => ['labels' => [], 'data' => []],
                'orders_by_week'      => ['labels' => [], 'orders' => [], 'units' => []],
                'top_products'        => ['labels' => [], 'data' => []],
                'daily_activity'      => ['labels' => [], 'revenue' => [], 'orders' => []],
                'category_scorecard'  => [
                    'labels'   => ['Margin', 'Units sold', 'Revenue share', 'Velocity', 'Growth'],
                    'datasets' => [],
                ],
                'weekly_margin' => ['labels' => [], 'revenue' => [], 'cost' => []],
                'department_metrics' => [
                    'revenue_share' => ['labels' => [], 'data' => []],
                    'units_share'   => ['labels' => [], 'data' => []],
                    'comparison'    => [
                        'labels'  => array_map(static fn (Department $case): string => $case->label(), Department::cases()),
                        'revenue' => array_fill(0, count(Department::cases()), 0.0),
                        'profit'  => array_fill(0, count(Department::cases()), 0.0),
                        'units'   => array_fill(0, count(Department::cases()), 0),
                    ],
                ],
            ],
        ];
    }

    private function saleDateWhere(string $alias): string
    {
        return "DATE({$alias}.sale_date) >= ? AND DATE({$alias}.sale_date) < ?";
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
        if (! $db->tableExists('sale_items')) {
            $saleRow = $db->query(
                'SELECT COUNT(*) AS orders, COALESCE(SUM(grand_total), 0) AS revenue ' .
                'FROM sales WHERE ' . $this->saleDateWhere('sales'),
                [$startDate, $endDate]
            )->getRowArray();

            $revenue = (float) ($saleRow['revenue'] ?? 0);
            $orders  = (int) ($saleRow['orders'] ?? 0);

            return [
                'revenue'             => $revenue,
                'orders'              => $orders,
                'units'               => 0,
                'cost'                => 0.0,
                'profit'              => $revenue,
                'avg_order_value'     => $orders > 0 ? $revenue / $orders : 0.0,
                'profit_margin_pct'   => $revenue > 0 ? 100.0 : 0.0,
            ];
        }

        $saleRow = $db->query(
            'SELECT COUNT(*) AS orders, COALESCE(SUM(grand_total), 0) AS revenue ' .
            'FROM sales WHERE ' . $this->saleDateWhere('sales'),
            [$startDate, $endDate]
        )->getRowArray();

        $itemRow = $db->query(
            'SELECT COALESCE(SUM(si.qty), 0) AS units, ' .
            'COALESCE(SUM(si.qty * pv.cost_price), 0) AS cost ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'WHERE ' . $this->saleDateWhere('s'),
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
     * @param array<string, float|int> $yoyMtd
     * @param array{remaining_units: int, inventory_value: float, returned_pct: float} $inventory
     *
     * @return array<string, mixed>
     */
    private function buildKpis(array $mtd, array $prevMtd, array $yoyMtd, array $inventory): array
    {
        return [
            'revenue_mtd' => [
                'value'          => $mtd['revenue'],
                'change_pct'     => $this->percentChange((float) $prevMtd['revenue'], (float) $mtd['revenue']),
                'change_pct_yoy' => $this->percentChange((float) $yoyMtd['revenue'], (float) $mtd['revenue']),
            ],
            'orders' => [
                'value'          => $mtd['orders'],
                'units'          => $mtd['units'],
                'change_pct'     => $this->percentChange((float) $prevMtd['orders'], (float) $mtd['orders']),
                'change_pct_yoy' => $this->percentChange((float) $yoyMtd['orders'], (float) $mtd['orders']),
                'units_change_pct'     => $this->percentChange((float) $prevMtd['units'], (float) $mtd['units']),
                'units_change_pct_yoy' => $this->percentChange((float) $yoyMtd['units'], (float) $mtd['units']),
            ],
            'profit' => [
                'value'          => round((float) $mtd['profit'], 2),
                'change_pct'     => $this->percentChange((float) $prevMtd['profit'], (float) $mtd['profit']),
                'change_pct_yoy' => $this->percentChange((float) $yoyMtd['profit'], (float) $mtd['profit']),
            ],
            'profit_margin_pct' => [
                'value'          => round($mtd['profit_margin_pct'], 1),
                'change_pts'     => round($mtd['profit_margin_pct'] - $prevMtd['profit_margin_pct'], 1),
                'change_pts_yoy' => round($mtd['profit_margin_pct'] - $yoyMtd['profit_margin_pct'], 1),
            ],
            'inventory' => [
                'remaining_units' => (int) $inventory['remaining_units'],
                'inventory_value' => round((float) $inventory['inventory_value'], 2),
                'returned_pct'    => round((float) $inventory['returned_pct'], 1),
            ],
        ];
    }

    /**
     * @return array{remaining_units: int, inventory_value: float, returned_pct: float}
     */
    private function inventorySummary(BaseConnection $db): array
    {
        $empty = [
            'remaining_units' => 0,
            'inventory_value' => 0.0,
            'returned_pct'    => 0.0,
        ];

        if (! $db->tableExists('inventory') || ! $db->tableExists('product_variants')) {
            return $empty;
        }

        $inventoryRow = $db->query(
            'SELECT COALESCE(SUM(i.quantity), 0) AS remaining_units, ' .
            'COALESCE(SUM(i.quantity * pv.cost_price), 0) AS inventory_value ' .
            'FROM inventory i ' .
            'INNER JOIN product_variants pv ON pv.id = i.variant_id ' .
            'WHERE pv.is_active = 1'
        )->getRowArray();

        $remainingUnits = (int) ($inventoryRow['remaining_units'] ?? 0);
        $inventoryValue = (float) ($inventoryRow['inventory_value'] ?? 0);

        $purchasedUnits = 0;
        if ($db->tableExists('purchase_items')) {
            $purchaseRow    = $db->query('SELECT COALESCE(SUM(qty), 0) AS purchased_units FROM purchase_items')->getRowArray();
            $purchasedUnits = (int) ($purchaseRow['purchased_units'] ?? 0);
        }

        $returnedPct = $purchasedUnits > 0
            ? ($remainingUnits / $purchasedUnits) * 100
            : 0.0;

        return [
            'remaining_units' => $remainingUnits,
            'inventory_value' => $inventoryValue,
            'returned_pct'    => $returnedPct,
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
            'FROM sales WHERE ' . $this->saleDateWhere('sales') . ' ' .
            'GROUP BY MONTH(sale_date)',
            [$start, $end]
        )->getResultArray();

        $profitRows = [];
        if ($db->tableExists('sale_items')) {
            $profitRows = $db->query(
                'SELECT MONTH(s.sale_date) AS month_num, ' .
                'COALESCE(SUM(si.line_total), 0) - COALESCE(SUM(si.qty * pv.cost_price), 0) AS profit ' .
                'FROM sale_items si ' .
                'INNER JOIN sales s ON s.id = si.sale_id ' .
                'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
                'WHERE ' . $this->saleDateWhere('s') . ' ' .
                'GROUP BY MONTH(s.sale_date)',
                [$start, $end]
            )->getResultArray();
        }

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
        if (! $db->fieldExists('warehouse_id', 'sales') || ! $db->tableExists('warehouses')) {
            return ['labels' => [], 'data' => []];
        }

        $rows = $db->query(
            'SELECT COALESCE(w.name, "Unassigned") AS label, COALESCE(SUM(s.grand_total), 0) AS amount ' .
            'FROM sales s ' .
            'LEFT JOIN warehouses w ON w.id = s.warehouse_id ' .
            'WHERE ' . $this->saleDateWhere('s') . ' ' .
            'GROUP BY s.warehouse_id, w.name ' .
            'ORDER BY amount DESC',
            [$startDate, $endDate]
        )->getResultArray();

        return $this->labelsAndValues($rows, 'label', 'amount');
    }

    /**
     * @return array{labels: list<string>, data: list<float>}
     */
    private function salesByGender(BaseConnection $db, string $startDate, string $endDate): array
    {
        if (! $db->tableExists('sale_items')
            || ! $db->fieldExists('gender', 'products')) {
            return ['labels' => [], 'data' => []];
        }

        $rows = $db->query(
            'SELECT COALESCE(NULLIF(TRIM(p.gender), ""), "unspecified") AS gender_key, ' .
            'COALESCE(SUM(si.line_total), 0) AS amount ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $this->saleDateWhere('s') . ' ' .
            'GROUP BY p.gender ' .
            'ORDER BY amount DESC',
            [$startDate, $endDate]
        )->getResultArray();

        $labels = [];
        $data   = [];

        foreach ($rows as $row) {
            $labels[] = $this->genderLabel((string) ($row['gender_key'] ?? ''));
            $data[]   = (float) ($row['amount'] ?? 0);
        }

        if ($labels === []) {
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function genderLabel(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '' || $value === 'unspecified') {
            return 'Unspecified';
        }

        if (! Gender::isValid($value)) {
            return ucfirst($value);
        }

        return Gender::from($value)->label();
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
     * @param list<array<string, mixed>> $rows
     *
     * @return array{
     *     revenue_share: array{labels: list<string>, data: list<float>},
     *     units_share: array{labels: list<string>, data: list<int>},
     *     comparison: array{
     *         labels: list<string>,
     *         revenue: list<float>,
     *         profit: list<float>,
     *         units: list<int>
     *     }
     * }
     */
    private function formatDepartmentMetrics(array $rows): array
    {
        $aggregated = [];

        foreach ($rows as $row) {
            $key = strtolower(trim((string) ($row['department_key'] ?? 'unspecified')));
            if ($key === '') {
                $key = 'unspecified';
            }

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'revenue' => 0.0,
                    'profit'  => 0.0,
                    'units'   => 0,
                ];
            }

            $aggregated[$key]['revenue'] += (float) ($row['revenue'] ?? 0);
            $aggregated[$key]['profit']  += (float) ($row['profit'] ?? 0);
            $aggregated[$key]['units']   += (int) ($row['units'] ?? 0);
        }

        $comparisonLabels  = [];
        $comparisonRevenue = [];
        $comparisonProfit  = [];
        $comparisonUnits   = [];

        foreach (Department::cases() as $case) {
            $key = $case->value;
            $comparisonLabels[]  = $case->label();
            $comparisonRevenue[] = round($aggregated[$key]['revenue'] ?? 0.0, 2);
            $comparisonProfit[]  = round($aggregated[$key]['profit'] ?? 0.0, 2);
            $comparisonUnits[]   = (int) ($aggregated[$key]['units'] ?? 0);
            unset($aggregated[$key]);
        }

        foreach ($aggregated as $key => $totals) {
            if (($totals['revenue'] ?? 0) <= 0 && ($totals['units'] ?? 0) <= 0) {
                continue;
            }

            $comparisonLabels[]  = $this->departmentLabel($key);
            $comparisonRevenue[] = round((float) ($totals['revenue'] ?? 0), 2);
            $comparisonProfit[]  = round((float) ($totals['profit'] ?? 0), 2);
            $comparisonUnits[]   = (int) ($totals['units'] ?? 0);
        }

        $pieRevenueLabels = [];
        $pieRevenueData   = [];
        $pieUnitsLabels   = [];
        $pieUnitsData     = [];

        foreach ($comparisonLabels as $index => $label) {
            $revenue = (float) ($comparisonRevenue[$index] ?? 0);
            $units   = (int) ($comparisonUnits[$index] ?? 0);

            if ($revenue > 0) {
                $pieRevenueLabels[] = $label;
                $pieRevenueData[]   = $revenue;
            }
            if ($units > 0) {
                $pieUnitsLabels[] = $label;
                $pieUnitsData[]   = $units;
            }
        }

        return [
            'revenue_share' => [
                'labels' => $pieRevenueLabels,
                'data'   => $pieRevenueData,
            ],
            'units_share' => [
                'labels' => $pieUnitsLabels,
                'data'   => $pieUnitsData,
            ],
            'comparison' => [
                'labels'  => $comparisonLabels,
                'revenue' => $comparisonRevenue,
                'profit'  => $comparisonProfit,
                'units'   => $comparisonUnits,
            ],
        ];
    }

    /**
     * @return array{
     *     revenue_share: array{labels: list<string>, data: list<float>},
     *     units_share: array{labels: list<string>, data: list<int>},
     *     comparison: array{
     *         labels: list<string>,
     *         revenue: list<float>,
     *         profit: list<float>,
     *         units: list<int>
     *     }
     * }
     */
    private function departmentMetrics(BaseConnection $db): array
    {
        $empty = [
            'revenue_share' => ['labels' => [], 'data' => []],
            'units_share'   => ['labels' => [], 'data' => []],
            'comparison'    => ['labels' => [], 'revenue' => [], 'profit' => [], 'units' => []],
        ];

        if (! $db->tableExists('sale_items') || ! $db->tableExists('products')) {
            return $empty;
        }

        $deptKey      = $this->departmentKeySql($db);
        $categoryJoin = $db->tableExists('categories')
            ? 'LEFT JOIN categories c ON c.id = p.category_id'
            : '';

        $rows = $db->query(
            "SELECT {$deptKey} AS department_key, " .
            'COALESCE(SUM(si.line_total), 0) AS revenue, ' .
            'COALESCE(SUM(si.line_total), 0) - COALESCE(SUM(si.qty * pv.cost_price), 0) AS profit, ' .
            'COALESCE(SUM(si.qty), 0) AS units ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            $categoryJoin . ' ' .
            "GROUP BY {$deptKey} " .
            'ORDER BY revenue DESC'
        )->getResultArray();

        if ($rows === []) {
            return $this->formatDepartmentMetrics([]);
        }

        return $this->formatDepartmentMetrics($rows);
    }

    /**
     * @return array{labels: list<string>, data: list<float>}
     */
    private function revenueByCategory(BaseConnection $db, string $startDate, string $endDate): array
    {
        if (! $db->tableExists('sale_items')) {
            return ['labels' => [], 'data' => []];
        }

        $rows = $db->query(
            'SELECT COALESCE(c.name, "Uncategorized") AS label, COALESCE(SUM(si.line_total), 0) AS amount ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'LEFT JOIN categories c ON c.id = p.category_id ' .
            'WHERE ' . $this->saleDateWhere('s') . ' ' .
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
        if ($db->tableExists('sale_payments')) {
            $rows = $db->query(
                'SELECT COALESCE(NULLIF(TRIM(sp.payment_method), ""), "other") AS label, COUNT(*) AS cnt ' .
                'FROM sale_payments sp ' .
                'INNER JOIN sales s ON s.id = sp.sale_id ' .
                'WHERE ' . $this->saleDateWhere('s') . ' ' .
                'GROUP BY sp.payment_method ' .
                'ORDER BY cnt DESC',
                [$startDate, $endDate]
            )->getResultArray();

            return $this->labelsAndValues($rows, 'label', 'cnt', true);
        }

        $rows = $db->query(
            'SELECT COALESCE(NULLIF(TRIM(payment_method), ""), "other") AS label, COUNT(*) AS cnt ' .
            'FROM sales ' .
            'WHERE ' . $this->saleDateWhere('sales') . ' ' .
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
                'SELECT COUNT(*) AS orders FROM sales WHERE ' . $this->saleDateWhere('sales'),
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )->getRowArray();

            $unitRow = ['units' => 0];
            if ($db->tableExists('sale_items')) {
                $unitRow = $db->query(
                    'SELECT COALESCE(SUM(si.qty), 0) AS units ' .
                    'FROM sale_items si ' .
                    'INNER JOIN sales s ON s.id = si.sale_id ' .
                    'WHERE ' . $this->saleDateWhere('s'),
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )->getRowArray() ?? ['units' => 0];
            }

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
        if (! $db->tableExists('sale_items')) {
            return ['labels' => [], 'data' => []];
        }

        $rows = $db->query(
            'SELECT p.name AS label, COALESCE(SUM(si.qty), 0) AS units ' .
            'FROM sale_items si ' .
            'INNER JOIN sales s ON s.id = si.sale_id ' .
            'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
            'INNER JOIN products p ON p.id = pv.product_id ' .
            'WHERE ' . $this->saleDateWhere('s') . ' ' .
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
                'FROM sales WHERE ' . $this->saleDateWhere('sales'),
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
        if (! $db->tableExists('sale_items') || ! $db->tableExists('categories')) {
            return [
                'labels'   => ['Margin', 'Units sold', 'Revenue share', 'Velocity', 'Growth'],
                'datasets' => [],
            ];
        }

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
            'WHERE ' . $this->saleDateWhere('s') . ' ' .
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
                'WHERE ' . $this->saleDateWhere('sales'),
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )->getRowArray();

            $costRow = ['cost' => 0];
            if ($db->tableExists('sale_items')) {
                $costRow = $db->query(
                    'SELECT COALESCE(SUM(si.qty * pv.cost_price), 0) AS cost ' .
                    'FROM sale_items si ' .
                    'INNER JOIN sales s ON s.id = si.sale_id ' .
                    'INNER JOIN product_variants pv ON pv.id = si.product_variant_id ' .
                    'WHERE ' . $this->saleDateWhere('s'),
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )->getRowArray() ?? ['cost' => 0];
            }

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
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
