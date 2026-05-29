<?php
/** @var string $month */
/** @var int $warehouseId */
/** @var list<array<string, mixed>> $warehouses */
/** @var array{grouped: array<string, array{rowspan: int, warehouses: array<int, array{name: string, rowspan: int, lines: list<array<string, mixed>>}>}>, month_total: array{total_income: float, profit: float}, warehouse_totals: list<array{warehouse_id: int|null, warehouse_name: string, total_income: float, profit: float}>} $report */

$formatMoney = static fn (float $value): string => number_format($value, 2, '.', ',');
$grouped = $report['grouped'] ?? [];
$monthTotal = $report['month_total'] ?? ['total_income' => 0.0, 'profit' => 0.0];
$warehouseTotals = $report['warehouse_totals'] ?? [];
$monthLabel = \DateTimeImmutable::createFromFormat('Y-m', $month)?->format('F Y') ?? $month;
?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Sales Monthly Statistics<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .sales-stats-table th,
    .sales-stats-table td {
        vertical-align: middle;
    }
    .sales-stats-table .date-cell {
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
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid px-5 py-4">
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Sales Monthly Statistics</h1>
            <p class="text-muted mb-0">Comprehensive sales history grouped by date and warehouse.</p>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="get" action="<?= site_url('inventory/sales-statistics') ?>" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="monthFilter" class="form-label text-secondary mb-1">Month</label>
                        <input type="month" id="monthFilter" name="month" class="form-control" value="<?= esc($month) ?>">
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
                <h2 class="h6 fw-semibold mb-3"><?= esc($monthLabel) ?> Sales History</h2>

                <?php if ($grouped === []): ?>
                    <div class="text-muted">No sales found for the selected filters.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm sales-stats-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 120px;">Date</th>
                                    <th style="width: 140px;">Warehouse</th>
                                    <th>Product</th>
                                    <th style="width: 120px;">Style</th>
                                    <th>Sizes</th>
                                    <th class="text-end" style="width: 90px;">Quantity</th>
                                    <th class="text-end" style="width: 120px;">Total Income</th>
                                    <th class="text-end" style="width: 120px;">Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grouped as $saleDate => $dateGroup): ?>
                                    <?php $dateRendered = false; ?>
                                    <?php foreach ($dateGroup['warehouses'] as $warehouseGroup): ?>
                                        <?php $warehouseRendered = false; ?>
                                        <?php foreach ($warehouseGroup['lines'] as $line): ?>
                                            <tr>
                                                <?php if (! $dateRendered): ?>
                                                    <td class="date-cell" rowspan="<?= (int) $dateGroup['rowspan'] ?>">
                                                        <?= esc($saleDate) ?>
                                                    </td>
                                                    <?php $dateRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (! $warehouseRendered): ?>
                                                    <td class="warehouse-cell" rowspan="<?= (int) $warehouseGroup['rowspan'] ?>">
                                                        <?= esc($warehouseGroup['name']) ?>
                                                    </td>
                                                    <?php $warehouseRendered = true; ?>
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
                                    <td colspan="6" class="text-end">Month Total Revenue</td>
                                    <td class="text-end"><?= esc($formatMoney((float) $monthTotal['total_income'])) ?></td>
                                    <td class="text-end"><?= esc($formatMoney((float) $monthTotal['profit'])) ?></td>
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
                    <h2 class="h6 fw-semibold mb-3">Warehouse Totals for <?= esc($monthLabel) ?></h2>
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
                                    <td class="text-end">Month Total</td>
                                    <td class="text-end"><?= esc($formatMoney((float) $monthTotal['total_income'])) ?></td>
                                    <td class="text-end"><?= esc($formatMoney((float) $monthTotal['profit'])) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?= $this->endSection() ?>
