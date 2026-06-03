<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .metric-card .metric-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
    .chart-wrap { position: relative; height: 260px; }
    .chart-wrap-sm { height: 220px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid px-5 py-4">
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Dashboard</h1>
            <p class="text-muted mb-0">Store metrics from sales data (month to date).</p>
        </div>

        <div id="dashboardAlert" class="alert alert-danger d-none" role="alert"></div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Revenue (MTD)</div>
                        <div id="kpiRevenue" class="metric-value text-primary">—</div>
                        <div id="kpiRevenueChange" class="small text-muted">—</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Orders</div>
                        <div id="kpiOrders" class="metric-value">—</div>
                        <div id="kpiOrdersChange" class="small text-muted">—</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Avg. order value</div>
                        <div id="kpiAov" class="metric-value">—</div>
                        <div class="small text-muted">From completed sales</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Profit margin</div>
                        <div id="kpiMargin" class="metric-value">—</div>
                        <div id="kpiMarginChange" class="small text-muted">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Revenue &amp; profit trend</h2>
                        <p class="small text-muted mb-3">Monthly performance for <?= date('Y') ?>.</p>
                        <div class="chart-wrap"><canvas id="chartRevenueTrend"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Sales by warehouse</h2>
                        <p class="small text-muted mb-3">Share of revenue by warehouse (MTD).</p>
                        <div class="chart-wrap chart-wrap-sm"><canvas id="chartChannelPie"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Revenue by category</h2>
                        <p class="small text-muted mb-3">Category sales (MTD).</p>
                        <div class="chart-wrap"><canvas id="chartCategoryBar"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Payment methods</h2>
                        <p class="small text-muted mb-3">Order count by payment type (MTD).</p>
                        <div class="chart-wrap"><canvas id="chartPaymentDoughnut"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Orders vs units sold</h2>
                        <p class="small text-muted mb-3">Weekly orders and units sold.</p>
                        <div class="chart-wrap"><canvas id="chartOrdersStacked"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Top products</h2>
                        <p class="small text-muted mb-3">Best sellers by units (MTD).</p>
                        <div class="chart-wrap"><canvas id="chartTopProducts"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Daily revenue &amp; orders</h2>
                        <p class="small text-muted mb-3">Last 7 days.</p>
                        <div class="chart-wrap"><canvas id="chartTrafficArea"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Category scorecard</h2>
                        <p class="small text-muted mb-3">Top categories (normalized, MTD).</p>
                        <div class="chart-wrap"><canvas id="chartCategoryRadar"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Revenue vs cost</h2>
                        <p class="small text-muted mb-3">Weekly margin view.</p>
                        <div class="chart-wrap"><canvas id="chartMixedMargin"></canvas></div>
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
    const DASHBOARD_API_URL = "<?= site_url('api/dashboard') ?>";
    const palette = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];
    const gridColor = 'rgba(0,0,0,0.06)';
    const fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    const charts = {};

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    function money(n) {
        return '$' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function pctText(value, suffix) {
        const sign = value > 0 ? '+' : '';
        const cls = value > 0 ? 'text-success' : (value < 0 ? 'text-danger' : 'text-muted');
        return { text: sign + value + suffix, cls };
    }

    function titleCasePayment(label) {
        return String(label || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function applyKpis(kpis) {
        const rev = kpis.revenue_mtd || {};
        const ord = kpis.orders || {};
        const aov = kpis.avg_order_value || {};
        const margin = kpis.profit_margin_pct || {};

        document.getElementById('kpiRevenue').textContent = money(rev.value);
        const revCh = pctText(rev.change_pct ?? 0, '% vs last month');
        const revEl = document.getElementById('kpiRevenueChange');
        revEl.textContent = revCh.text + ' vs last month';
        revEl.className = 'small ' + revCh.cls;

        document.getElementById('kpiOrders').textContent = Number(ord.value || 0).toLocaleString();
        const ordCh = pctText(ord.change_pct ?? 0, '%');
        const ordEl = document.getElementById('kpiOrdersChange');
        ordEl.textContent = ordCh.text + ' vs last month';
        ordEl.className = 'small ' + ordCh.cls;

        document.getElementById('kpiAov').textContent = money(aov.value);

        document.getElementById('kpiMargin').textContent = (margin.value ?? 0) + '%';
        const mCh = pctText(margin.change_pts ?? 0, ' pts');
        const mEl = document.getElementById('kpiMarginChange');
        mEl.textContent = mCh.text + ' vs last month';
        mEl.className = 'small ' + mCh.cls;
    }

    function initCharts(payload) {
        const c = payload.charts || {};

        charts.revenueTrend = new Chart(document.getElementById('chartRevenueTrend'), {
            type: 'line',
            data: {
                labels: c.revenue_trend?.labels || [],
                datasets: [
                    { label: 'Revenue', data: c.revenue_trend?.revenue || [], borderColor: palette[0], backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.3 },
                    { label: 'Profit', data: c.revenue_trend?.profit || [], borderColor: palette[1], borderDash: [4, 4], tension: 0.3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { callback: v => '$' + (v / 1000) + 'k' } },
                    x: { grid: { display: false } }
                }
            }
        });

        charts.channelPie = new Chart(document.getElementById('chartChannelPie'), {
            type: 'pie',
            data: {
                labels: c.sales_by_warehouse?.labels || [],
                datasets: [{ data: c.sales_by_warehouse?.data || [], backgroundColor: palette }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        charts.categoryBar = new Chart(document.getElementById('chartCategoryBar'), {
            type: 'bar',
            data: {
                labels: c.revenue_by_category?.labels || [],
                datasets: [{ label: 'Revenue ($)', data: c.revenue_by_category?.data || [], backgroundColor: palette[0] }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { display: false } } }
            }
        });

        const payLabels = (c.payment_methods?.labels || []).map(titleCasePayment);
        charts.paymentDoughnut = new Chart(document.getElementById('chartPaymentDoughnut'), {
            type: 'doughnut',
            data: {
                labels: payLabels,
                datasets: [{ data: c.payment_methods?.data || [], backgroundColor: [palette[0], palette[1], palette[2], palette[4], palette[5], palette[6]] }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '58%' }
        });

        charts.ordersStacked = new Chart(document.getElementById('chartOrdersStacked'), {
            type: 'bar',
            data: {
                labels: c.orders_by_week?.labels || [],
                datasets: [
                    { label: 'Orders', data: c.orders_by_week?.orders || [], backgroundColor: palette[0] },
                    { label: 'Units sold', data: c.orders_by_week?.units || [], backgroundColor: palette[3] }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: gridColor } }
                }
            }
        });

        charts.topProducts = new Chart(document.getElementById('chartTopProducts'), {
            type: 'bar',
            data: {
                labels: c.top_products?.labels || [],
                datasets: [{ label: 'Units sold', data: c.top_products?.data || [], backgroundColor: palette[5] }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { beginAtZero: true, grid: { color: gridColor } }, y: { grid: { display: false } } }
            }
        });

        charts.trafficArea = new Chart(document.getElementById('chartTrafficArea'), {
            type: 'line',
            data: {
                labels: c.daily_activity?.labels || [],
                datasets: [
                    {
                        label: 'Revenue',
                        data: c.daily_activity?.revenue || [],
                        borderColor: palette[6],
                        backgroundColor: 'rgba(253,126,20,0.25)',
                        fill: true,
                        tension: 0.35
                    },
                    {
                        label: 'Orders',
                        data: c.daily_activity?.orders || [],
                        borderColor: palette[0],
                        backgroundColor: 'rgba(13,110,253,0.15)',
                        fill: true,
                        tension: 0.35,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, position: 'left', grid: { color: gridColor }, ticks: { callback: v => '$' + v } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        const radarStyles = [
            { border: palette[0], bg: 'rgba(13,110,253,0.2)' },
            { border: palette[1], bg: 'rgba(25,135,84,0.2)' },
        ];
        const radarDatasets = (c.category_scorecard?.datasets || []).map((ds, i) => {
            const style = radarStyles[i % radarStyles.length];
            return { label: ds.label, data: ds.data, borderColor: style.border, backgroundColor: style.bg };
        });
        charts.categoryRadar = new Chart(document.getElementById('chartCategoryRadar'), {
            type: 'radar',
            data: {
                labels: c.category_scorecard?.labels || [],
                datasets: radarDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { r: { beginAtZero: true, max: 100, ticks: { stepSize: 20 } } }
            }
        });

        charts.mixedMargin = new Chart(document.getElementById('chartMixedMargin'), {
            data: {
                labels: c.weekly_margin?.labels || [],
                datasets: [
                    { type: 'bar', label: 'Revenue', data: c.weekly_margin?.revenue || [], backgroundColor: palette[0], yAxisID: 'y' },
                    { type: 'line', label: 'Cost', data: c.weekly_margin?.cost || [], borderColor: palette[3], tension: 0.3, yAxisID: 'y' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { display: false } } }
            }
        });
    }

    function showError(msg) {
        const el = document.getElementById('dashboardAlert');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    fetch(DASHBOARD_API_URL)
        .then(r => {
            if (!r.ok) throw new Error('Failed to load dashboard (' + r.status + ')');
            return r.json();
        })
        .then(json => {
            const data = json.data || {};
            applyKpis(data.kpis || {});
            initCharts(data);
        })
        .catch(err => showError(err.message || 'Could not load dashboard data.'));
})();
</script>
<?= $this->endSection() ?>
