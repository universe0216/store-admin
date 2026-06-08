<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Price Analyzer<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .chart-wrap { position: relative; height: 280px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Price Analyzer</h1>
                <p class="text-muted mb-0 small" id="periodSummary">Units sold by price bucket for the last 12 months.</p>
            </div>
            <a href="<?= site_url('sells') ?>" class="btn btn-outline-secondary btn-sm">Sales History</a>
        </div>

        <div id="statsAlert" class="alert alert-danger d-none mb-4" role="alert"></div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4 col-lg-3">
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
                </div>
            </div>
        </div>

        <div class="row g-3" id="departmentCharts"></div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/chart.umd.js') ?>"></script>
<script>
(function () {
    const API_URL = "<?= site_url('api/price-analyzer') ?>";
    const charts = {};
    const palette = '#0d6efd';
    const gridColor = 'rgba(0,0,0,0.06)';
    let loadTimer = null;

    function scheduleLoadStatistics() {
        clearTimeout(loadTimer);
        loadTimer = setTimeout(loadStatistics, 250);
    }

    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

    function showError(msg) {
        const el = document.getElementById('statsAlert');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideError() {
        document.getElementById('statsAlert').classList.add('d-none');
    }

    function formatDate(value) {
        if (!value) return '';
        const parts = String(value).split('-');
        if (parts.length !== 3) return value;
        const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function destroyCharts() {
        Object.keys(charts).forEach(key => {
            if (charts[key]) {
                charts[key].destroy();
                delete charts[key];
            }
        });
    }

    function renderCharts(data) {
        destroyCharts();

        const container = document.getElementById('departmentCharts');
        const buckets = data.buckets || [];
        const departments = data.departments || [];

        if (data.from && data.to) {
            document.getElementById('periodSummary').textContent =
                'Units sold by unit price bucket from ' + formatDate(data.from) + ' to ' + formatDate(data.to) + '.';
        }

        if (departments.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-light border mb-0">No department data available.</div></div>';
            return;
        }

        container.innerHTML = departments.map(dept => {
            const canvasId = 'chart-' + dept.key;
            return '<div class="col-12 col-lg-6">' +
                '<div class="card shadow-sm h-100">' +
                '<div class="card-body">' +
                '<h2 class="h6 fw-semibold mb-1">' + dept.label + '</h2>' +
                '<p class="small text-muted mb-3">Total units sold per price range.</p>' +
                '<div class="chart-wrap"><canvas id="' + canvasId + '"></canvas></div>' +
                '</div></div></div>';
        }).join('');

        departments.forEach(dept => {
            const canvasId = 'chart-' + dept.key;
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            charts[dept.key] = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: buckets,
                    datasets: [{
                        label: 'Units sold',
                        data: dept.units || [],
                        backgroundColor: palette + 'cc',
                        borderColor: palette,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: gridColor }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label(ctx) {
                                    return Number(ctx.raw || 0).toLocaleString() + ' units';
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    function loadStatistics() {
        hideError();

        const params = new URLSearchParams();
        const warehouseId = String(document.getElementById('warehouseFilter').value || '').trim();
        if (warehouseId !== '') {
            params.set('warehouse_id', warehouseId);
        }

        const url = params.toString() ? API_URL + '?' + params.toString() : API_URL;

        fetch(url)
            .then(r => {
                if (!r.ok) throw new Error('Failed to load price analyzer data (' + r.status + ')');
                return r.json();
            })
            .then(json => renderCharts(json.data || {}))
            .catch(err => showError(err.message || 'Could not load price analyzer data.'));
    }

    document.getElementById('warehouseFilter').addEventListener('change', scheduleLoadStatistics);

    loadStatistics();
})();
</script>
<?= $this->endSection() ?>
