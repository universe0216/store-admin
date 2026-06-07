<?php

use App\Enums\Department;

/** @var int $year */
/** @var string $department */
/** @var list<Department> $departments */
/** @var array{grouped: array<string, array{rowspan: int, total_units: int, total_cost: float, departments: array<string, array{name: string, rowspan: int, total_units: int, total_cost: float, lines: list<array{supplier_name: string, quantity: int, total_cost: float}>}>}>, year_total: array{total_units: int, total_cost: float}} $report */

$formatMoney = static fn (float $value): string => number_format($value, 2, '.', ',');
$formatMonthKey = static function (string $monthKey): string {
    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $monthKey . '-01');

    return $dt !== false ? $dt->format('F Y') : $monthKey;
};

$grouped = $report['grouped'] ?? [];
$yearTotal = $report['year_total'] ?? ['total_units' => 0, 'total_cost' => 0.0];
$currentYear = (int) date('Y');
$minYear = 2025;
?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Purchase Yearly Statistics<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .purchase-stats-table th,
    .purchase-stats-table td {
        vertical-align: middle;
    }
    .purchase-stats-table thead th.month-col {
        background-color: #cfe2ff !important;
        font-weight: 600;
    }
    .purchase-stats-table thead th.dept-col {
        background-color: #ffe0b2 !important;
        font-weight: 600;
    }
    .purchase-stats-table thead .subheader th.month-col,
    .purchase-stats-table thead .subheader th.dept-col {
        font-size: 0.8125rem;
        font-weight: 500;
    }
    .purchase-stats-table .period-cell {
        background-color: #e7f1ff;
        font-weight: 600;
        white-space: nowrap;
    }
    .purchase-stats-table .department-cell {
        background-color: #fff8e1;
        font-weight: 500;
        white-space: nowrap;
    }
    .purchase-stats-table tfoot td {
        background-color: #f1f3f5;
        font-weight: 600;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid px-5 py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Purchase Yearly Statistics</h1>
                <p class="text-muted mb-0">Purchase history grouped by month, department, and supplier.</p>
            </div>
            <a href="<?= site_url('purchases/history') ?>" class="btn btn-outline-secondary btn-sm">Purchase History</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="get" action="<?= site_url('purchases/yearly-statistics') ?>" class="row g-3 align-items-end">
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
                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 fw-semibold mb-3"><?= esc((string) $year) ?> Purchase History</h2>

                <?php if ($grouped === []): ?>
                    <div class="text-muted">No purchases found for the selected filters.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm purchase-stats-table mb-0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="month-col" style="width: 120px;">Month</th>
                                    <th colspan="2" class="text-center month-col">Month Total</th>
                                    <th rowspan="2" class="dept-col" style="width: 120px;">Department</th>
                                    <th colspan="2" class="text-center dept-col">Dept Total</th>
                                    <th rowspan="2">Supplier</th>
                                    <th rowspan="2" class="text-end" style="width: 100px;">Total Units</th>
                                    <th rowspan="2" class="text-end" style="width: 120px;">Total Costs</th>
                                </tr>
                                <tr class="subheader">
                                    <th class="text-end month-col" style="width: 80px;">Units</th>
                                    <th class="text-end month-col" style="width: 100px;">Costs</th>
                                    <th class="text-end dept-col" style="width: 80px;">Units</th>
                                    <th class="text-end dept-col" style="width: 100px;">Costs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grouped as $purchaseMonth => $monthGroup): ?>
                                    <?php $monthRendered = false; ?>
                                    <?php $monthTotalsRendered = false; ?>
                                    <?php foreach ($monthGroup['departments'] as $departmentGroup): ?>
                                        <?php $departmentRendered = false; ?>
                                        <?php $departmentTotalsRendered = false; ?>
                                        <?php foreach ($departmentGroup['lines'] as $line): ?>
                                            <tr>
                                                <?php if (! $monthRendered): ?>
                                                    <td class="period-cell" rowspan="<?= (int) $monthGroup['rowspan'] ?>">
                                                        <?= esc($formatMonthKey($purchaseMonth)) ?>
                                                    </td>
                                                    <?php $monthRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (! $monthTotalsRendered): ?>
                                                    <td class="period-cell text-end" rowspan="<?= (int) $monthGroup['rowspan'] ?>">
                                                        <?= (int) ($monthGroup['total_units'] ?? 0) ?>
                                                    </td>
                                                    <td class="period-cell text-end" rowspan="<?= (int) $monthGroup['rowspan'] ?>">
                                                        <?= esc($formatMoney((float) ($monthGroup['total_cost'] ?? 0))) ?>
                                                    </td>
                                                    <?php $monthTotalsRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (! $departmentRendered): ?>
                                                    <td class="department-cell" rowspan="<?= (int) $departmentGroup['rowspan'] ?>">
                                                        <?= esc($departmentGroup['name']) ?>
                                                    </td>
                                                    <?php $departmentRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (! $departmentTotalsRendered): ?>
                                                    <td class="department-cell text-end" rowspan="<?= (int) $departmentGroup['rowspan'] ?>">
                                                        <?= (int) ($departmentGroup['total_units'] ?? 0) ?>
                                                    </td>
                                                    <td class="department-cell text-end" rowspan="<?= (int) $departmentGroup['rowspan'] ?>">
                                                        <?= esc($formatMoney((float) ($departmentGroup['total_cost'] ?? 0))) ?>
                                                    </td>
                                                    <?php $departmentTotalsRendered = true; ?>
                                                <?php endif; ?>

                                                <td><?= esc($line['supplier_name']) ?></td>
                                                <td class="text-end"><?= (int) $line['quantity'] ?></td>
                                                <td class="text-end"><?= esc($formatMoney((float) $line['total_cost'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-end">Year Total</td>
                                    <td class="text-end"><?= (int) ($yearTotal['total_units'] ?? 0) ?></td>
                                    <td class="text-end"><?= esc($formatMoney((float) ($yearTotal['total_cost'] ?? 0))) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>
