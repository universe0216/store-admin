<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Exchange Rates<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4 px-5">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Exchange Rates</h1>
        <p class="text-muted mb-0">Latest rates: 1 USD = quote currency. Saving a rate adds a new entry (history is kept).</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Current Rates</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">Currency</th>
                                    <th>Name</th>
                                    <th style="width: 160px;" class="text-end">1 USD =</th>
                                    <th style="width: 170px;">Effective</th>
                                    <th style="width: 300px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ratesTableBody">
                                <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Set Rate</h2>
                    <form id="rateForm" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Quote currency</label>
                            <select id="quoteCurrencyInput" class="form-select" required>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">1 USD =</label>
                            <input id="rateInput" type="number" class="form-control" min="0" step="any" required placeholder="0.00">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveRateBtn">Save Rate</button>
                        </div>
                        <div class="col-12">
                            <div id="messageBox" class="small fw-semibold"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rateHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rateHistoryTitle">Rate History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-end" style="width: 140px;">1 USD =</th>
                                <th style="width: 180px;">Effective</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody id="rateHistoryBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rateGraphModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rateGraphTitle">Rate Chart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="rateGraphEmpty" class="text-center text-muted py-4 d-none">No rate history to display.</div>
                <div id="rateGraphWrap" style="height: 320px; position: relative;">
                    <canvas id="rateHistoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/chart.umd.js') ?>"></script>
<script>
    const EXCHANGE_RATES_API_URL = "<?= site_url('api/exchange-rates') ?>";
    const CURRENCIES_API_URL = "<?= site_url('api/currencies') ?>";
    const BASE_CURRENCY = "USD";
    let rateHistoryModal = null;
    let rateGraphModal = null;
    let rateChartInstance = null;
    let quoteCurrencies = [];

    function formatRate(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) {
            return "—";
        }
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDateTime(value) {
        if (!value) {
            return "—";
        }
        const d = new Date(String(value).replace(" ", "T"));
        return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
    }

    function formatChartLabel(value) {
        const d = new Date(String(value).replace(" ", "T"));
        if (Number.isNaN(d.getTime())) {
            return String(value || "");
        }
        return d.toLocaleDateString() + " " + d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    }

    function destroyRateChart() {
        if (rateChartInstance) {
            rateChartInstance.destroy();
            rateChartInstance = null;
        }
    }

    function populateQuoteSelect(selected) {
        const select = $("#quoteCurrencyInput");
        select.empty();
        if (!quoteCurrencies.length) {
            select.append('<option value="">No currencies (add non-USD first)</option>');
            return;
        }
        quoteCurrencies.forEach(function (row) {
            const code = row.code || "";
            select.append(`<option value="${code}">${code} — ${row.name || ""}</option>`);
        });
        if (selected) {
            select.val(selected);
        }
    }

    function renderRatesTable(rows) {
        const tbody = $("#ratesTableBody");
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">No quote currencies. Add currencies other than USD first.</td></tr>');
            return;
        }

        rows.forEach(function (row) {
            const code = row.quote_currency || "";
            const rateText = row.rate != null ? formatRate(row.rate) : "—";
            const tr = $("<tr>");
            tr.append(`<td>${code}</td>`);
            tr.append(`<td>${row.currency_name || ""}</td>`);
            tr.append(`<td class="text-end fw-semibold">${rateText}</td>`);
            tr.append(`<td>${formatDateTime(row.effective_at)}</td>`);
            tr.append(
                `<td>
                    <button type="button" class="btn btn-sm btn-outline-primary me-1 set-rate-btn" data-code="${code}" data-rate="${row.rate != null ? row.rate : ""}">Set</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1 history-btn" data-code="${code}">History</button>
                    <button type="button" class="btn btn-sm btn-outline-info graph-btn" data-code="${code}">Graph</button>
                </td>`
            );
            tbody.append(tr);
        });
    }

    function loadLatestRates() {
        return $.getJSON(EXCHANGE_RATES_API_URL).done(function (res) {
            renderRatesTable(res.data || []);
        });
    }

    function loadCurrencies() {
        return $.getJSON(CURRENCIES_API_URL).done(function (res) {
            quoteCurrencies = (res.data || []).filter(function (row) {
                return String(row.code || "").toUpperCase() !== BASE_CURRENCY;
            });
            populateQuoteSelect();
        });
    }

    function openHistory(code) {
        $("#rateHistoryTitle").text("Rate History — " + code);
        $("#rateHistoryBody").html('<tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>');
        rateHistoryModal.show();

        $.getJSON(EXCHANGE_RATES_API_URL, { quote_currency: code, limit: 50 })
            .done(function (res) {
                const tbody = $("#rateHistoryBody");
                tbody.empty();
                const rows = res.data || [];
                if (!rows.length) {
                    tbody.append('<tr><td colspan="3" class="text-center text-muted">No history.</td></tr>');
                    return;
                }
                rows.forEach(function (row) {
                    tbody.append(
                        `<tr>
                            <td class="text-end">${formatRate(row.rate)}</td>
                            <td>${formatDateTime(row.effective_at)}</td>
                            <td>${row.source || "manual"}</td>
                        </tr>`
                    );
                });
            })
            .fail(function (xhr) {
                $("#rateHistoryBody").html(
                    `<tr><td colspan="3" class="text-danger">${xhr.responseJSON?.message || "Failed to load history."}</td></tr>`
                );
            });
    }

    function openGraph(code) {
        $("#rateGraphTitle").text("Rate Chart — 1 USD = " + code);
        $("#rateGraphEmpty").addClass("d-none");
        $("#rateGraphWrap").removeClass("d-none");
        destroyRateChart();
        rateGraphModal.show();

        $.getJSON(EXCHANGE_RATES_API_URL, { quote_currency: code, limit: 100 })
            .done(function (res) {
                const rows = (res.data || []).slice().reverse();
                if (!rows.length) {
                    destroyRateChart();
                    $("#rateGraphWrap").addClass("d-none");
                    $("#rateGraphEmpty").removeClass("d-none");
                    return;
                }

                const labels = rows.map(function (row) {
                    return formatChartLabel(row.effective_at);
                });
                const values = rows.map(function (row) {
                    return Number(row.rate);
                });

                const ctx = document.getElementById("rateHistoryChart");
                rateChartInstance = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [{
                            label: "1 USD = " + code,
                            data: values,
                            borderColor: "#0d6efd",
                            backgroundColor: "rgba(13, 110, 253, 0.12)",
                            fill: true,
                            tension: 0.25,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: "index", intersect: false },
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        return "1 USD = " + formatRate(ctx.parsed.y);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                min: 0,
                                title: { display: true, text: code },
                                ticks: {
                                    callback: function (v) {
                                        return formatRate(v);
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 12
                                }
                            }
                        }
                    }
                });
            })
            .fail(function (xhr) {
                destroyRateChart();
                $("#rateGraphWrap").addClass("d-none");
                $("#rateGraphEmpty")
                    .removeClass("d-none")
                    .text(xhr.responseJSON?.message || "Failed to load rate history.");
            });
    }

    $(function () {
        rateHistoryModal = new bootstrap.Modal(document.getElementById("rateHistoryModal"));
        rateGraphModal = new bootstrap.Modal(document.getElementById("rateGraphModal"));

        document.getElementById("rateGraphModal").addEventListener("hidden.bs.modal", function () {
            destroyRateChart();
        });

        loadCurrencies().always(function () {
            loadLatestRates().fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load exchange rates.", true);
            });
        });

        $("#rateForm").on("submit", function (e) {
            e.preventDefault();
            const quote = String($("#quoteCurrencyInput").val() || "").toUpperCase();
            const rate = Number($("#rateInput").val() || 0);

            if (!quote) {
                setMessage("Select a quote currency.", true);
                return;
            }
            if (rate <= 0) {
                setMessage("Rate must be greater than 0.", true);
                return;
            }

            $("#saveRateBtn").prop("disabled", true);
            setMessage("");

            $.ajax({
                url: EXCHANGE_RATES_API_URL,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    quote_currency: quote,
                    rate: rate
                })
            }).done(function (res) {
                setMessage(res.message || "Exchange rate saved.");
                $("#rateInput").val("");
                loadLatestRates();
            }).fail(function (xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to save rate.", true);
            }).always(function () {
                $("#saveRateBtn").prop("disabled", false);
            });
        });

        $(document).on("click", ".set-rate-btn", function () {
            const code = $(this).data("code");
            const rate = $(this).data("rate");
            populateQuoteSelect(code);
            if (rate !== "" && rate != null) {
                $("#rateInput").val(rate);
            }
            $("#quoteCurrencyInput").focus();
        });

        $(document).on("click", ".history-btn", function () {
            openHistory(String($(this).data("code") || ""));
        });

        $(document).on("click", ".graph-btn", function () {
            openGraph(String($(this).data("code") || ""));
        });
    });
</script>
<?= $this->endSection() ?>
