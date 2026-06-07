<?php

use App\Enums\Department;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Visual Statistics<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .chart-wrap { position: relative; height: 320px; }
    .year-chip {
        cursor: pointer;
        user-select: none;
    }
    .year-chip input { margin-right: 0.35rem; }
</style>
<?= $this->endSection() ?>

<?php
$currentYear = (int) date('Y');
$minYear = 2025;
?>
<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Visual Statistics</h1>
                <p class="text-muted mb-0 small">Monthly revenue, profit, units sold, and orders by year.</p>
            </div>
            <a href="<?= site_url('sells') ?>" class="btn btn-outline-secondary btn-sm">Sales History</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    
                    <div class="col-2">
                        <label for="yearSelect" class="form-label text-secondary mb-1 small">Add year</label>
                        <div class="input-group input-group-sm">
                            <select id="yearSelect" class="form-select">
                                <?php for ($y = $currentYear; $y >= $minYear; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="button" id="addYearBtn" class="btn btn-outline-primary">Add</button>
                        </div>
                    </div>
                    <div class="col-10">
                        <label class="form-label text-secondary mb-2 small fw-semibold">Years (select multiple)</label>
                        <div id="yearCheckboxList" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="col-3">
                        <label for="departmentFilter" class="form-label text-secondary mb-1 small">Department</label>
                        <select id="departmentFilter" class="form-select form-select-sm">
                            <option value="">All departments</option>
                            <?php foreach ($departments as $case) : ?>
                                <option value="<?= esc($case->value) ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <label for="warehouseFilter" class="form-label text-secondary mb-1 small">Warehouse</label>
                        <select id="warehouseFilter" class="form-select form-select-sm">
                            <option value="">All warehouses</option>
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option value="<?= esc((string) ($warehouse['id'] ?? '')) ?>">
                                    <?= esc((string) ($warehouse['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-4 d-flex flex-wrap gap-2 align-items-end">
                        <button type="button" id="selectAllYearsBtn" class="btn btn-outline-secondary btn-sm">Select all</button>
                        <button type="button" id="clearYearsBtn" class="btn btn-outline-secondary btn-sm">Clear</button>
                    </div>
                </div>
                <div id="statsAlert" class="alert alert-danger d-none mt-3 mb-0" role="alert"></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Monthly revenue</h2>
                        <p class="small text-muted mb-3">Grand total by month.</p>
                        <div class="chart-wrap"><canvas id="chartRevenue"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Monthly profit</h2>
                        <p class="small text-muted mb-3">Line total minus cost of goods sold.</p>
                        <div class="chart-wrap"><canvas id="chartProfit"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Monthly units sold</h2>
                        <p class="small text-muted mb-3">Total quantity sold per month.</p>
                        <div class="chart-wrap"><canvas id="chartUnits"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Monthly orders</h2>
                        <p class="small text-muted mb-3">Number of sales per month.</p>
                        <div class="chart-wrap"><canvas id="chartOrders"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/chart.umd.js') ?>"></script>
<script>
(function () {
    const API_URL = "<?= site_url('api/sales-visual-statistics') ?>";
    const CURRENT_YEAR = <?= $currentYear ?>;
    const MIN_YEAR = <?= $minYear ?>;
    const palette = ['#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1', '#20c997', '#ffc107', '#6c757d'];
    const gridColor = 'rgba(0,0,0,0.06)';
    const charts = { revenue: null, profit: null, units: null, orders: null };
    let loadTimer = null;

    function scheduleLoadStatistics() {
        clearTimeout(loadTimer);
        loadTimer = setTimeout(loadStatistics, 250);
    }

    function selectorYears() {
        const years = [];
        for (let y = CURRENT_YEAR; y >= MIN_YEAR; y--) {
            years.push(y);
        }
        return years;
    }

    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    function showError(msg) {
        const el = document.getElementById('statsAlert');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideError() {
        document.getElementById('statsAlert').classList.add('d-none');
    }

    function sortedUniqueYears(years) {
        return [...new Set(years.map(y => parseInt(y, 10)).filter(y => y >= 2000 && y <= 2100))].sort((a, b) => a - b);
    }

    function getSelectedYears() {
        const checked = [];
        document.querySelectorAll('#yearCheckboxList input[type="checkbox"]:checked').forEach(cb => {
            checked.push(parseInt(cb.value, 10));
        });
        return sortedUniqueYears(checked);
    }

    function renderYearCheckboxes(selectedYears) {
        const container = document.getElementById('yearCheckboxList');
        const selected = new Set(selectedYears);
        const merged = selectorYears();

        container.innerHTML = merged.map(year => {
            const isChecked = selected.has(year) ? 'checked' : '';
            return '<label class="year-chip badge rounded-pill text-bg-light border px-3 py-2">' +
                '<input type="checkbox" value="' + year + '" ' + isChecked + '> ' + year +
                '</label>';
        }).join('');
    }

    function yearColor(index) {
        return palette[index % palette.length];
    }

    function buildDatasets(series, field) {
        const years = Object.keys(series || {}).sort();
        return years.map((year, index) => ({
            label: year,
            data: (series[year] && series[year][field]) ? series[year][field] : [],
            borderColor: yearColor(index),
            backgroundColor: field === 'orders' || field === 'units' ? yearColor(index) : yearColor(index) + '33',
            borderWidth: 2,
            tension: 0.3,
            fill: false
        }));
    }

    function updateChart(chartKey, canvasId, series, field, yTickFormatter) {
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const datasets = buildDatasets(series, field);
        const canvas = document.getElementById(canvasId);

        if (charts[chartKey]) {
            charts[chartKey].destroy();
            charts[chartKey] = null;
        }

        if (datasets.length === 0) {
            return;
        }

        charts[chartKey] = new Chart(canvas, {
            type: field === 'orders' || field === 'units' ? 'bar' : 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            callback: yTickFormatter || (v => v)
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const v = Number(ctx.raw || 0);
                                if (field === 'orders') {
                                    return ctx.dataset.label + ': ' + v.toLocaleString() + ' orders';
                                }
                                if (field === 'units') {
                                    return ctx.dataset.label + ': ' + v.toLocaleString() + ' units';
                                }
                                return ctx.dataset.label + ': $' + v.toLocaleString(undefined, { maximumFractionDigits: 0 });
                            }
                        }
                    }
                }
            }
        });
    }

    function renderCharts(data) {
        const series = data.series || {};
        const labels = data.labels || [];
        if (labels.length) {
            /* labels from API; charts use fixed month names */
        }
        updateChart('revenue', 'chartRevenue', series, 'revenue', v => '$' + Number(v).toLocaleString());
        updateChart('profit', 'chartProfit', series, 'profit', v => '$' + Number(v).toLocaleString());
        updateChart('units', 'chartUnits', series, 'units', v => Number(v).toLocaleString());
        updateChart('orders', 'chartOrders', series, 'orders', v => Number(v).toLocaleString());
    }

    function loadStatistics() {
        const years = getSelectedYears();
        if (years.length === 0) {
            showError('Select at least one year.');
            return;
        }
        hideError();

        const params = new URLSearchParams();
        years.forEach(y => params.append('years[]', String(y)));

        const department = String(document.getElementById('departmentFilter').value || '').trim();
        const warehouseId = String(document.getElementById('warehouseFilter').value || '').trim();
        if (department !== '') {
            params.set('department', department);
        }
        if (warehouseId !== '') {
            params.set('warehouse_id', warehouseId);
        }

        fetch(API_URL + '?' + params.toString())
            .then(r => {
                if (!r.ok) throw new Error('Failed to load statistics (' + r.status + ')');
                return r.json();
            })
            .then(json => {
                renderCharts(json.data || {});
            })
            .catch(err => showError(err.message || 'Could not load statistics.'));
    }

    document.getElementById('yearCheckboxList').addEventListener('change', function (e) {
        if (e.target.matches('input[type="checkbox"]')) {
            scheduleLoadStatistics();
        }
    });

    document.getElementById('departmentFilter').addEventListener('change', scheduleLoadStatistics);
    document.getElementById('warehouseFilter').addEventListener('change', scheduleLoadStatistics);

    document.getElementById('selectAllYearsBtn').addEventListener('click', () => {
        document.querySelectorAll('#yearCheckboxList input[type="checkbox"]').forEach(cb => { cb.checked = true; });
        scheduleLoadStatistics();
    });

    document.getElementById('clearYearsBtn').addEventListener('click', () => {
        document.querySelectorAll('#yearCheckboxList input[type="checkbox"]').forEach(cb => { cb.checked = false; });
        scheduleLoadStatistics();
    });

    document.getElementById('addYearBtn').addEventListener('click', () => {
        const year = parseInt(document.getElementById('yearSelect').value, 10);
        if (!year || year < MIN_YEAR || year > CURRENT_YEAR) {
            showError('Select a year from ' + MIN_YEAR + ' to ' + CURRENT_YEAR + '.');
            return;
        }
        hideError();
        const selected = getSelectedYears();
        if (!selected.includes(year)) {
            selected.push(year);
        }
        renderYearCheckboxes(sortedUniqueYears(selected));
        const cb = document.querySelector('#yearCheckboxList input[value="' + year + '"]');
        if (cb) cb.checked = true;
        scheduleLoadStatistics();
    });

    renderYearCheckboxes([CURRENT_YEAR]);
    loadStatistics();
})();
</script>
<?= $this->endSection() ?>
