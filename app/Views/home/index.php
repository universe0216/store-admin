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
            <p class="text-muted mb-0">Sample dashboard charts (dummy data). Wire to API when ready.</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Revenue (MTD)</div>
                        <div class="metric-value text-primary">$48,320</div>
                        <div class="small text-success">+12.4% vs last month</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Orders</div>
                        <div class="metric-value">1,284</div>
                        <div class="small text-success">+8.1%</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Avg. order value</div>
                        <div class="metric-value">$37.60</div>
                        <div class="small text-muted">Target $40.00</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm metric-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Conversion rate</div>
                        <div class="metric-value">3.2%</div>
                        <div class="small text-danger">-0.3 pts</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Revenue &amp; profit trend</h2>
                        <p class="small text-muted mb-3">Line chart — monthly performance (common for sales over time).</p>
                        <div class="chart-wrap"><canvas id="chartRevenueTrend"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Sales by channel</h2>
                        <p class="small text-muted mb-3">Pie chart — share of revenue by source.</p>
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
                        <p class="small text-muted mb-3">Bar chart — compare category sales.</p>
                        <div class="chart-wrap"><canvas id="chartCategoryBar"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Payment methods</h2>
                        <p class="small text-muted mb-3">Doughnut chart — order count by payment type.</p>
                        <div class="chart-wrap"><canvas id="chartPaymentDoughnut"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Orders vs returns</h2>
                        <p class="small text-muted mb-3">Stacked bar — weekly fulfillment quality.</p>
                        <div class="chart-wrap"><canvas id="chartOrdersStacked"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Top products</h2>
                        <p class="small text-muted mb-3">Horizontal bar — best sellers by units.</p>
                        <div class="chart-wrap"><canvas id="chartTopProducts"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Traffic vs conversions</h2>
                        <p class="small text-muted mb-3">Area chart — sessions and converted orders.</p>
                        <div class="chart-wrap"><canvas id="chartTrafficArea"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Category scorecard</h2>
                        <p class="small text-muted mb-3">Radar chart — margin, velocity, returns (normalized).</p>
                        <div class="chart-wrap"><canvas id="chartCategoryRadar"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-1">Revenue vs cost</h2>
                        <p class="small text-muted mb-3">Mixed bar + line — margin view by week.</p>
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
    const palette = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];
    const gridColor = 'rgba(0,0,0,0.06)';
    const fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const revenue = [32000, 35500, 30100, 38900, 41200, 44800, 42100, 46500, 43900, 47200, 49100, 48320];
    const profit = [8200, 9100, 7400, 10200, 10800, 11900, 11100, 12400, 11600, 12800, 13200, 12900];

    new Chart(document.getElementById('chartRevenueTrend'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                { label: 'Revenue', data: revenue, borderColor: palette[0], backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.3 },
                { label: 'Profit', data: profit, borderColor: palette[1], borderDash: [4, 4], tension: 0.3 }
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

    new Chart(document.getElementById('chartChannelPie'), {
        type: 'pie',
        data: {
            labels: ['In-store', 'Website', 'Marketplace', 'Wholesale'],
            datasets: [{ data: [38, 34, 18, 10], backgroundColor: palette }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('chartCategoryBar'), {
        type: 'bar',
        data: {
            labels: ['Apparel', 'Footwear', 'Accessories', 'Electronics', 'Home'],
            datasets: [{ label: 'Revenue ($)', data: [14200, 11800, 8600, 7200, 6520], backgroundColor: palette[0] }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('chartPaymentDoughnut'), {
        type: 'doughnut',
        data: {
            labels: ['Card', 'Cash', 'Bank transfer', 'Wallet'],
            datasets: [{ data: [52, 22, 16, 10], backgroundColor: [palette[0], palette[1], palette[2], palette[4]] }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '58%' }
    });

    new Chart(document.getElementById('chartOrdersStacked'), {
        type: 'bar',
        data: {
            labels: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6'],
            datasets: [
                { label: 'Orders', data: [210, 245, 228, 260, 252, 289], backgroundColor: palette[0] },
                { label: 'Returns', data: [18, 22, 15, 28, 19, 24], backgroundColor: palette[3] }
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

    new Chart(document.getElementById('chartTopProducts'), {
        type: 'bar',
        data: {
            labels: ['Classic Tee', 'Runner Pro', 'Leather Belt', 'Denim Jacket', 'Cap Logo'],
            datasets: [{ label: 'Units sold', data: [420, 385, 310, 268, 241], backgroundColor: palette[5] }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, grid: { color: gridColor } }, y: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('chartTrafficArea'), {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Sessions',
                    data: [4200, 3900, 4100, 4500, 4800, 6200, 5800],
                    borderColor: palette[6],
                    backgroundColor: 'rgba(253,126,20,0.25)',
                    fill: true,
                    tension: 0.35
                },
                {
                    label: 'Orders',
                    data: [128, 118, 125, 142, 156, 198, 184],
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
                y: { beginAtZero: true, position: 'left', grid: { color: gridColor } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartCategoryRadar'), {
        type: 'radar',
        data: {
            labels: ['Margin', 'Sell-through', 'Stock health', 'Return rate', 'Growth'],
            datasets: [
                { label: 'Apparel', data: [72, 85, 70, 65, 80], borderColor: palette[0], backgroundColor: 'rgba(13,110,253,0.2)' },
                { label: 'Footwear', data: [68, 78, 82, 72, 75], borderColor: palette[1], backgroundColor: 'rgba(25,135,84,0.2)' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { r: { beginAtZero: true, max: 100, ticks: { stepSize: 20 } } }
        }
    });

    new Chart(document.getElementById('chartMixedMargin'), {
        data: {
            labels: ['W1', 'W2', 'W3', 'W4'],
            datasets: [
                { type: 'bar', label: 'Revenue', data: [11200, 12400, 11800, 12900], backgroundColor: palette[0], yAxisID: 'y' },
                { type: 'line', label: 'Cost', data: [8400, 9100, 8800, 9600], borderColor: palette[3], tension: 0.3, yAxisID: 'y' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { display: false } } }
        }
    });
})();
</script>
<?= $this->endSection() ?>
