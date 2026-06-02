<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Cash Balances<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4 px-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Cash Balances</h1>
            <p class="text-muted mb-0">Asset cash and bank accounts with balances in account currency and USD.</p>
        </div>
        <a href="<?= site_url('finance') ?>" class="btn btn-outline-secondary btn-sm">View Transactions</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <span class="text-secondary small">Total Cash (USD)</span>
                    <div id="totalBalanceUsd" class="fw-bold fs-4 text-primary">0.00</div>
                </div>
                <div class="col-12 col-md-4">
                    <span class="text-secondary small">Accounts</span>
                    <div id="accountCount" class="fw-semibold fs-5">0</div>
                </div>
            </div>
            <div id="loadError" class="text-danger small mt-2"></div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold mb-3">Account Balances</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 55px;">#</th>
                            <th style="width: 90px;">Code</th>
                            <th>Name</th>
                            <th style="width: 80px;">Currency</th>
                            <th style="width: 150px;" class="text-end">Balance</th>
                            <th style="width: 130px;" class="text-end">Balance (USD)</th>
                            <th>Payment Methods</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="balancesTableBody">
                        <tr>
                            <td colspan="8" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const BALANCES_API_URL = "<?= site_url('api/transactions/balances') ?>";
    const TRANSACTIONS_URL = "<?= site_url('finance') ?>";
    const BASE_CURRENCY = "USD";

    function formatMoney(value) {
        const n = Number(value || 0);
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderRows(rows) {
        const tbody = $("#balancesTableBody");
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">No cash accounts found.</td></tr>');
            return;
        }

        rows.forEach(function (row, index) {
            const methods = (row.payment_methods || []).join(", ") || "—";
            const currency = String(row.currency_code || BASE_CURRENCY).toUpperCase();
            const balanceOriginal = Number(row.balance_original ?? row.balance_usd ?? 0);
            const balanceUsd = Number(row.balance_usd ?? 0);
            const balanceCell = currency === BASE_CURRENCY
                ? formatMoney(balanceUsd)
                : `${formatMoney(balanceOriginal)} <span class="text-muted small">${currency}</span>`;

            const tr = $("<tr>");
            tr.append(`<td>${index + 1}</td>`);
            tr.append(`<td><code>${row.code || ""}</code></td>`);
            tr.append(`<td>${row.name || ""}</td>`);
            tr.append(`<td>${currency}</td>`);
            tr.append(`<td class="text-end fw-semibold">${balanceCell}</td>`);
            tr.append(`<td class="text-end text-muted">${formatMoney(balanceUsd)}</td>`);
            tr.append(`<td class="small">${methods}</td>`);
            tr.append(
                `<td>
                    <a href="${TRANSACTIONS_URL}" class="btn btn-sm btn-outline-primary">Transactions</a>
                </td>`
            );
            tbody.append(tr);
        });
    }

    function loadBalances() {
        $("#loadError").text("");
        $("#balancesTableBody").html(
            '<tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>'
        );

        $.getJSON(BALANCES_API_URL)
            .done(function (res) {
                const summary = res.summary || {};
                $("#totalBalanceUsd").text(formatMoney(summary.total_balance_usd));
                $("#accountCount").text(String(summary.account_count ?? 0));
                renderRows(res.data || []);
            })
            .fail(function (xhr) {
                $("#loadError").text(xhr.responseJSON?.message || "Failed to load balances.");
                $("#balancesTableBody").html(
                    '<tr><td colspan="8" class="text-center text-danger">Failed to load.</td></tr>'
                );
            });
    }

    $(function () {
        loadBalances();
    });
</script>
<?= $this->endSection() ?>
