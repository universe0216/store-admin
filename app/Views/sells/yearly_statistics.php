<?php

use App\Enums\Department;

/** @var int $year */
/** @var int $warehouseId */
/** @var string $department */
/** @var list<array<string, mixed>> $warehouses */
/** @var list<Department> $departments */
/** @var array{grouped: array<string, array{rowspan: int, total_income: float, profit: float, orders: int, quantity: int, warehouses: array<int, array{name: string, rowspan: int, total_income: float, profit: float, orders: int, quantity: int, lines: list<array<string, mixed>>}>}>, year_total: array{total_income: float, profit: float}, warehouse_totals: list<array{warehouse_id: int|null, warehouse_name: string, total_income: float, profit: float}>} $report */

$formatMoney = static fn (float $value): string => number_format($value, 2, '.', ',');
$formatMonthKey = static function (string $monthKey): string {
    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $monthKey . '-01');

    return $dt !== false ? $dt->format('F Y') : $monthKey;
};

$renderMetricsStack = static function (float $revenue, int $units, int $orders, float $profit) use ($formatMoney): void {
    $profitClass = $profit >= 0 ? 'metrics-profit-positive' : 'metrics-profit-negative';
    ?>
    <div class="metrics-stack text-end">
        <div class="metrics-revenue"><span class="metrics-label">Revenue</span> <?= esc($formatMoney($revenue)) ?></div>
        <div class="metrics-units"><span class="metrics-label">Units</span> <?= (int) $units ?></div>
        <div class="metrics-orders"><span class="metrics-label">Orders</span> <?= (int) $orders ?></div>
        <div class="metrics-profit <?= esc($profitClass) ?>"><span class="metrics-label">Profit</span> <?= esc($formatMoney($profit)) ?></div>
    </div>
    <?php
};

$grouped = $report['grouped'] ?? [];
$yearTotal = $report['year_total'] ?? ['total_income' => 0.0, 'profit' => 0.0];
$warehouseTotals = $report['warehouse_totals'] ?? [];
$currentYear = (int) date('Y');
$minYear = 2025;
?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Sales Yearly Statistics<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .sales-stats-table th,
    .sales-stats-table td {
        vertical-align: middle;
    }
    .sales-stats-table .period-cell {
        background-color: #f8f9fa;
        font-weight: 600;
        white-space: nowrap;
    }
    .sales-stats-table .warehouse-cell {
        background-color: #fcfcfd;
        font-weight: 500;
        white-space: nowrap;
    }
    .sales-stats-table tfoot td {
        background-color: #f1f3f5;
        font-weight: 600;
    }
    .warehouse-totals-table th,
    .warehouse-totals-table td {
        vertical-align: middle;
    }
    .metrics-stack {
        line-height: 1.4;
        font-size: 0.875rem;
    }
    .metrics-stack .metrics-label {
        display: inline-block;
        min-width: 3.5rem;
        font-weight: 500;
    }
    .metrics-stack .metrics-revenue {
        color: #0d6efd;
        font-weight: 600;
    }
    .metrics-stack .metrics-units {
        color: #0d6efd;
        font-weight: 500;
    }
    .metrics-stack .metrics-orders {
        color: #6c757d;
        font-weight: 600;
    }
    .metrics-stack .metrics-profit {
        font-weight: 600;
    }
    .metrics-stack .metrics-profit-positive,
    .metrics-stack .metrics-profit-positive .metrics-label {
        color: #198754;
    }
    .metrics-stack .metrics-profit-negative,
    .metrics-stack .metrics-profit-negative .metrics-label {
        color: #dc3545;
    }
    .metrics-legend .legend-revenue { color: #0d6efd; }
    .metrics-legend .legend-orders { color: #6c757d; }
    .metrics-legend .legend-profit { color: #198754; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid px-5 py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Sales Yearly Statistics</h1>
                <p class="text-muted mb-0">Sales history grouped by month and warehouse.</p>
            </div>
            <a href="<?= site_url('inventory/sales-statistics') ?>" class="btn btn-outline-secondary btn-sm">Monthly Statistics</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="get" action="<?= site_url('sells/yearly-statistics') ?>" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="yearFilter" class="form-label text-secondary mb-1">Year</label>
                        <select id="yearFilter" name="year" class="form-select">
                            <?php for ($y = $currentYear; $y >= $minYear; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="departmentFilter" class="form-label text-secondary mb-1">Department</label>
                        <select id="departmentFilter" name="department" class="form-select">
                            <option value="">All departments</option>
                            <?php foreach ($departments as $case) : ?>
                                <option value="<?= esc($case->value) ?>" <?= $case->value === $department ? 'selected' : '' ?>>
                                    <?= esc($case->label()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="warehouseFilter" class="form-label text-secondary mb-1">Warehouse</label>
                        <select id="warehouseFilter" name="warehouse_id" class="form-select">
                            <option value="">All warehouses</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?= esc((string) ($warehouse['id'] ?? '')) ?>" <?= (int) ($warehouse['id'] ?? 0) === $warehouseId ? 'selected' : '' ?>>
                                    <?= esc((string) ($warehouse['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-semibold mb-3"><?= esc((string) $year) ?> Sales History</h2>

                <?php if ($grouped === []): ?>
                    <div class="text-muted">No sales found for the selected filters.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm sales-stats-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 120px;">Month</th>
                                    <th class="text-end" style="width: 110px;">
                                        Month Totals
                                        <div class="metrics-legend small fw-normal mt-1">
                                            <span class="legend-revenue">Revenue</span> ·
                                            <span class="legend-revenue">Units</span> ·
                                            <span class="legend-orders">Orders</span> ·
                                            <span class="legend-profit">Profit</span>
                                        </div>
                                    </th>
                                    <th style="width: 120px;">Warehouse</th>
                                    <th class="text-end" style="width: 110px;">
                                        WH Totals
                                        <div class="metrics-legend small fw-normal mt-1">
                                            <span class="legend-revenue">Revenue</span> ·
                                            <span class="legend-revenue">Units</span> ·
                                            <span class="legend-orders">Orders</span> ·
                                            <span class="legend-profit">Profit</span>
                                        </div>
                                    </th>
                                    <th>Product</th>
                                    <th style="width: 110px;">Style</th>
                                    <th>Sizes</th>
                                    <th class="text-end" style="width: 80px;">Qty</th>
                                    <th class="text-end" style="width: 100px;">Line Income</th>
                                    <th class="text-end" style="width: 100px;">Line Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grouped as $saleMonth => $monthGroup): ?>
                                    <?php $monthRendered = false; ?>
                                    <?php $monthTotalsRendered = false; ?>
                                    <?php foreach ($monthGroup['warehouses'] as $warehouseGroup): ?>
                                        <?php $warehouseRendered = false; ?>
                                        <?php $warehouseTotalsRendered = false; ?>
                                        <?php foreach ($warehouseGroup['lines'] as $line): ?>
                                            <tr>
                                                <?php if (! $monthRendered): ?>
                                                    <td class="period-cell" rowspan="<?= (int) $monthGroup['rowspan'] ?>">
                                                        <?= esc($formatMonthKey($saleMonth)) ?>
                                                    </td>
                                                    <?php $monthRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (! $monthTotalsRendered): ?>
                                                    <td class="period-cell" rowspan="<?= (int) $monthGroup['rowspan'] ?>">
                                                        <?php $renderMetricsStack(
                                                            (float) ($monthGroup['total_income'] ?? 0),
                                                            (int) ($monthGroup['quantity'] ?? 0),
                                                            (int) ($monthGroup['orders'] ?? 0),
                                                            (float) ($monthGroup['profit'] ?? 0)
                                                        ); ?>
                                                    </td>
                                                    <?php $monthTotalsRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (! $warehouseRendered): ?>
                                                    <td class="warehouse-cell" rowspan="<?= (int) $warehouseGroup['rowspan'] ?>">
                                                        <?= esc($warehouseGroup['name']) ?>
                                                    </td>
                                                    <?php $warehouseRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (! $warehouseTotalsRendered): ?>
                                                    <td class="warehouse-cell" rowspan="<?= (int) $warehouseGroup['rowspan'] ?>">
                                                        <?php $renderMetricsStack(
                                                            (float) ($warehouseGroup['total_income'] ?? 0),
                                                            (int) ($warehouseGroup['quantity'] ?? 0),
                                                            (int) ($warehouseGroup['orders'] ?? 0),
                                                            (float) ($warehouseGroup['profit'] ?? 0)
                                                        ); ?>
                                                    </td>
                                                    <?php $warehouseTotalsRendered = true; ?>
                                                <?php endif; ?>

                                                <td><?= esc($line['product_name']) ?></td>
                                                <td><?= esc($line['product_style']) ?></td>
                                                <td><?= esc($line['sizes']) ?></td>
                                                <td class="text-end"><?= (int) $line['quantity'] ?></td>
                                                <td class="text-end"><?= esc($formatMoney((float) $line['total_income'])) ?></td>
                                                <td class="text-end"><?= esc($formatMoney((float) $line['profit'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="8" class="text-end">Year Total</td>
                                    <td class="text-end"><?= esc($formatMoney((float) $yearTotal['total_income'])) ?></td>
                                    <td class="text-end"><?= esc($formatMoney((float) $yearTotal['profit'])) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($warehouseTotals !== []): ?>
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 fw-semibold mb-3">Warehouse Totals for <?= esc((string) $year) ?></h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm warehouse-totals-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Warehouse</th>
                                    <th class="text-end" style="width: 160px;">Total Revenue</th>
                                    <th class="text-end" style="width: 160px;">Total Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warehouseTotals as $warehouseTotal): ?>
                                    <tr>
                                        <td><?= esc($warehouseTotal['warehouse_name']) ?></td>
                                        <td class="text-end"><?= esc($formatMoney((float) $warehouseTotal['total_income'])) ?></td>
                                        <td class="text-end"><?= esc($formatMoney((float) $warehouseTotal['profit'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="text-end">Year Total</td>
                                    <td class="text-end"><?= esc($formatMoney((float) $yearTotal['total_income'])) ?></td>
                                    <td class="text-end"><?= esc($formatMoney((float) $yearTotal['profit'])) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?= $this->endSection() ?>
