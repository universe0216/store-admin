<?php

use App\Enums\Department;
use App\Enums\Gender;
use App\Enums\Season;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Purchase History<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Purchase History</h1>
                <p class="text-muted mb-0 small">All purchase records by product line.</p>
            </div>
            <a href="<?= site_url('purchases') ?>" class="btn btn-outline-secondary btn-sm">Back to Purchases</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label text-secondary mb-1 small">Search</label>
                        <input type="text" id="historySearchInput" class="form-control" placeholder="Product name or serial number">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">Department</label>
                        <select id="historyDepartmentFilter" class="form-select">
                            <option value="">All departments</option>
                            <?php foreach (Department::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">Gender</label>
                        <select id="historyGenderFilter" class="form-select">
                            <option value="">All genders</option>
                            <?php foreach (Gender::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1 small">Season</label>
                        <select id="historySeasonFilter" class="form-select">
                            <option value="">All seasons</option>
                            <?php foreach (Season::cases() as $case) : ?>
                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="button" id="applyHistorySearchBtn" class="btn btn-primary">Search</button>
                        <button type="button" id="clearHistorySearchBtn" class="btn btn-outline-secondary">Clear</button>
                    </div>
                </div>
                <div id="historyListError" class="text-danger small mb-2"></div>
                <div id="purchaseHistoryGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        purchaseHistory: "<?= site_url('api/purchase-history') ?>"
    };
    const HISTORY_PAGE_SIZE = 20;
    let historyListPage = 0;
    let historyListPageSize = HISTORY_PAGE_SIZE;
    let historyListTotal = 0;
    let historyListLoading = false;
    let suppressHistoryPageEvent = false;

    const historyGridSource = {
        localdata: [],
        datatype: "array",
        totalrecords: 0,
        datafields: [
            { name: "purchase_date", type: "string" },
            { name: "product_name", type: "string" },
            { name: "serial_number", type: "string" },
            { name: "style", type: "string" },
            { name: "reference_cost", type: "number" },
            { name: "reference_currency", type: "string" },
            { name: "exchange_rate", type: "number" },
            { name: "cost", type: "number" }
        ]
    };
    let historyGridAdapter = null;

    function formatMoney(value) {
        return Number(value || 0).toFixed(2);
    }

    function formatCost4(value) {
        return Number(value || 0).toFixed(4);
    }

    function formatReferenceCost(row) {
        const amount = formatCost4(row.reference_cost);
        const currency = String(row.reference_currency || "USD").toUpperCase();
        return `${amount} (${currency})`;
    }

    function formatExchangeRate(value) {
        const rate = Number(value || 0);
        if (rate <= 0) {
            return "—";
        }
        return rate.toFixed(4);
    }

    function setListError(msg) {
        $("#historyListError").text(msg || "");
    }

    function getFilterParams(page, perPage) {
        const params = {
            page: (page ?? historyListPage) + 1,
            per_page: perPage ?? historyListPageSize
        };
        const search = String($("#historySearchInput").val() || "").trim();
        const department = String($("#historyDepartmentFilter").val() || "").trim();
        const gender = String($("#historyGenderFilter").val() || "").trim();
        const season = String($("#historySeasonFilter").val() || "").trim();

        if (search !== "") {
            params.search = search;
        }
        if (department !== "") {
            params.department = department;
        }
        if (gender !== "") {
            params.gender = gender;
        }
        if (season !== "") {
            params.season = season;
        }
        return params;
    }

    function clearFilters() {
        $("#historySearchInput").val("");
        $("#historyDepartmentFilter").val("");
        $("#historyGenderFilter").val("");
        $("#historySeasonFilter").val("");
        loadPurchaseHistory(0, historyListPageSize, true);
    }

    function updateHistoryGridSource(rows, total, syncPage) {
        historyListTotal = total;
        suppressHistoryPageEvent = true;

        historyGridSource.localdata = rows;
        historyGridSource.totalrecords = total;
        historyGridAdapter.dataBind();
        $("#purchaseHistoryGrid").jqxGrid("updatebounddata");

        if (syncPage) {
            const paging = $("#purchaseHistoryGrid").jqxGrid("getpaginginformation");
            if (!paging || paging.pagenum !== historyListPage) {
                $("#purchaseHistoryGrid").jqxGrid("gotopage", historyListPage);
            }
        }

        window.setTimeout(function () {
            suppressHistoryPageEvent = false;
        }, 0);
    }

    function loadPurchaseHistory(page, perPage, resetPage) {
        setListError("");

        if (resetPage) {
            historyListPage = 0;
            if ($("#purchaseHistoryGrid").data("jqxGrid")) {
                suppressHistoryPageEvent = true;
                $("#purchaseHistoryGrid").jqxGrid("gotopage", 0);
                window.setTimeout(function () {
                    suppressHistoryPageEvent = false;
                }, 0);
            }
        } else if (page !== undefined && page !== null) {
            historyListPage = page;
        }
        if (perPage !== undefined && perPage !== null) {
            historyListPageSize = perPage;
        }

        const requestPage = historyListPage;
        const requestSize = historyListPageSize;
        historyListLoading = true;

        return $.getJSON(API_URLS.purchaseHistory, getFilterParams(requestPage, requestSize))
            .done(function (res) {
                const rows = res.data || [];
                const pagination = res.pagination || {};
                const total = Number(pagination.total || 0);

                historyListPage = Math.max(0, Number(pagination.page || 1) - 1);
                historyListPageSize = Number(pagination.per_page || requestSize);
                updateHistoryGridSource(rows, total, true);
            })
            .fail(function (xhr) {
                setListError(xhr.responseJSON?.message || "Failed to load purchase history.");
                updateHistoryGridSource([], 0, true);
            })
            .always(function () {
                historyListLoading = false;
            });
    }

    function initWidgets() {
        historyGridAdapter = new $.jqx.dataAdapter(historyGridSource);

        $("#purchaseHistoryGrid").jqxGrid({
            width: "100%",
            height: 560,
            columnsresize: true,
            pageable: true,
            pagesize: HISTORY_PAGE_SIZE,
            pagesizeoptions: ["10", "20", "50", "100"],
            virtualmode: true,
            rendergridrows: function () {
                return historyGridSource.localdata;
            },
            source: historyGridAdapter,
            columns: [
                { text: "Date", datafield: "purchase_date", width: 110 },
                { text: "Product Name", datafield: "product_name", width: 200 },
                { text: "Serial Number", datafield: "serial_number", width: 130 },
                { text: "Style", datafield: "style", width: 120 },
                {
                    text: "Reference Cost",
                    datafield: "reference_cost",
                    width: 140,
                    cellsalign: "right",
                    cellsrenderer: function (row, column, value, defaultHtml, columnSettings, rowData) {
                        const el = $(defaultHtml);
                        el.text(formatReferenceCost(rowData));
                        return el[0].outerHTML;
                    }
                },
                {
                    text: "Exchange Rate",
                    datafield: "exchange_rate",
                    width: 120,
                    cellsalign: "right",
                    cellsrenderer: function (row, column, value, defaultHtml) {
                        const el = $(defaultHtml);
                        el.text(formatExchangeRate(value));
                        return el[0].outerHTML;
                    }
                },
                {
                    text: "Cost",
                    datafield: "cost",
                    width: 110,
                    cellsalign: "right",
                    cellsformat: "f4"
                }
            ]
        });
    }

    $(function () {
        initWidgets();
        loadPurchaseHistory(0, HISTORY_PAGE_SIZE, true);

        $("#applyHistorySearchBtn").on("click", function () {
            loadPurchaseHistory(0, historyListPageSize, true);
        });
        $("#clearHistorySearchBtn").on("click", clearFilters);
        $("#historySearchInput").on("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                loadPurchaseHistory(0, historyListPageSize, true);
            }
        });
        $("#historyDepartmentFilter, #historyGenderFilter, #historySeasonFilter").on("change", function () {
            loadPurchaseHistory(0, historyListPageSize, true);
        });

        $("#purchaseHistoryGrid").on("pagechanged pagesizechanged", function (event) {
            if (suppressHistoryPageEvent || historyListLoading) {
                return;
            }

            const args = event.args || {};
            const newPage = Number(args.pagenum ?? 0);
            const newSize = Number(args.pagesize ?? historyListPageSize);

            if (newPage === historyListPage && newSize === historyListPageSize) {
                return;
            }

            loadPurchaseHistory(newPage, newSize, false);
        });
    });
</script>
<?= $this->endSection() ?>
