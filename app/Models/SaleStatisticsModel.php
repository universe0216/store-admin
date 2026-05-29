<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class SaleStatisticsModel extends BaseModel
{
    protected $table = 'sales';

    /**
     * @return array{
     *     grouped: array<string, array{rowspan: int, warehouses: array<int, array{name: string, rowspan: int, lines: list<array<string, mixed>>}>}>,
     *     month_total: array{total_income: float, profit: float},
     *     warehouse_totals: list<array{warehouse_id: int|null, warehouse_name: string, total_income: float, profit: float}>
     * }
     */
    public function getMonthlyReport(string $month, ?int $warehouseId = null): array
    {
        [$startDate, $endDate] = $this->monthBounds($month);
        $db = $this->db;

        $detailRows = $this->fetchDetailRows($db, $startDate, $endDate, $warehouseId);
        $rollupRows = $this->fetchRollupRows($db, $startDate, $endDate, $warehouseId);

        return [
            'grouped'          => $this->buildGroupedRows($detailRows),
            'month_total'      => $this->extractMonthTotal($rollupRows),
            'warehouse_totals' => $this->extractWarehouseTotals($rollupRows),
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
    private function fetchDetailRows(BaseConnection $db, string $startDate, string $endDate, ?int $warehouseId): array
    {
        $warehouseFilter = $warehouseId !== null && $warehouseId > 0
            ? ' AND s.warehouse_id = ' . (int) $warehouseId
            : '';

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
              {$warehouseFilter}
            GROUP BY DATE(s.sale_date), s.warehouse_id, w.name, p.id, p.name, pv.style
            ORDER BY sale_date DESC, warehouse_name ASC, product_name ASC, product_style ASC
            SQL;

        return $db->query($sql, [$startDate, $endDate])->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRollupRows(BaseConnection $db, string $startDate, string $endDate, ?int $warehouseId): array
    {
        $params = [$startDate, $endDate];
        $warehouseFilter = '';

        if ($warehouseId !== null && $warehouseId > 0) {
            $warehouseFilter = ' AND s.warehouse_id = ?';
            $params[] = $warehouseId;
        }

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
            LEFT JOIN warehouses w ON w.id = s.warehouse_id
            WHERE s.sale_date >= ?
              AND s.sale_date < ?
              {$warehouseFilter}
            GROUP BY s.warehouse_id WITH ROLLUP
            SQL;

        return $db->query($sql, $params)->getResultArray();
    }

    /**
     * @param list<array<string, mixed>> $detailRows
     *
     * @return array<string, array{rowspan: int, warehouses: array<int, array{name: string, rowspan: int, lines: list<array<string, mixed>>}>}>
     */
    private function buildGroupedRows(array $detailRows): array
    {
        $grouped = [];

        foreach ($detailRows as $row) {
            $date = (string) ($row['sale_date'] ?? '');
            $warehouseId = (int) ($row['warehouse_id'] ?? 0);

            if (! isset($grouped[$date])) {
                $grouped[$date] = [
                    'rowspan'    => 0,
                    'warehouses' => [],
                ];
            }

            if (! isset($grouped[$date]['warehouses'][$warehouseId])) {
                $grouped[$date]['warehouses'][$warehouseId] = [
                    'name'    => (string) ($row['warehouse_name'] ?? 'Unassigned'),
                    'rowspan' => 0,
                    'lines'   => [],
                ];
            }

            $grouped[$date]['warehouses'][$warehouseId]['lines'][] = [
                'product_name'  => (string) ($row['product_name'] ?? ''),
                'product_style' => (string) ($row['product_style'] ?? ''),
                'sizes'         => (string) ($row['sizes'] ?? ''),
                'quantity'      => (int) ($row['quantity'] ?? 0),
                'total_income'  => (float) ($row['total_income'] ?? 0),
                'profit'        => (float) ($row['profit'] ?? 0),
            ];
        }

        foreach ($grouped as &$dateGroup) {
            $dateRowspan = 0;

            foreach ($dateGroup['warehouses'] as &$warehouseGroup) {
                $warehouseGroup['rowspan'] = count($warehouseGroup['lines']);
                $dateRowspan += $warehouseGroup['rowspan'];
            }
            unset($warehouseGroup);

            $dateGroup['rowspan'] = $dateRowspan;
        }
        unset($dateGroup);

        return $grouped;
    }

    /**
     * @param list<array<string, mixed>> $rollupRows
     *
     * @return array{total_income: float, profit: float}
     */
    private function extractMonthTotal(array $rollupRows): array
    {
        foreach ($rollupRows as $row) {
            if ($row['warehouse_id'] === null) {
                return [
                    'total_income' => (float) ($row['total_income'] ?? 0),
                    'profit'       => (float) ($row['profit'] ?? 0),
                ];
            }
        }

        return ['total_income' => 0.0, 'profit' => 0.0];
    }

    /**
     * @param list<array<string, mixed>> $rollupRows
     *
     * @return list<array{warehouse_id: int|null, warehouse_name: string, total_income: float, profit: float}>
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
            ];
        }

        usort($totals, static fn (array $a, array $b): int => strcmp($a['warehouse_name'], $b['warehouse_name']));

        return $totals;
    }
}
