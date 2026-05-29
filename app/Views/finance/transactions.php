<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Transactions<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4 px-5">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Transactions</h1>
        <p class="text-muted mb-0">Cash and bank movements from purchases and sales.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-secondary mb-1">Reference No</label>
                    <input type="text" id="referenceNoFilter" class="form-control" placeholder="PO-... or SO-...">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-secondary mb-1">Accounts</label>
                    <div id="accountCodeFilter"></div>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                    <div>
                        <label class="form-label small text-secondary mb-1 d-block">From</label>
                        <div id="dateFromFilter"></div>
                    </div>
                    <span class="text-secondary pb-2">~</span>
                    <div>
                        <label class="form-label small text-secondary mb-1 d-block">To</label>
                        <div id="dateToFilter"></div>
                    </div>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2 justify-content-md-end">
                    <button type="button" id="applyFiltersBtn" class="btn btn-primary btn-sm">Search</button>
                    <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary btn-sm">Clear</button>
                </div>
            </div>

            <div id="summaryPanel" class="border rounded bg-light p-3 mb-3 small">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Current Balance</span>
                        <div id="metricTotalBalance" class="fw-bold fs-5 text-primary">0.00</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Period Debit</span>
                        <div id="metricTotalDebit" class="fw-semibold">0.00</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Period Credit</span>
                        <div id="metricTotalCredit" class="fw-semibold">0.00</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Rows</span>
                        <div id="metricTotalRows" class="fw-semibold">0</div>
                    </div>
                </div>
                <div id="accountBalancesRow" class="row g-2 mt-2 pt-2 border-top"></div>
                <div id="filterError" class="text-danger mt-2"></div>
            </div>

            <div id="transactionsGrid"></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        transactions: "<?= site_url('api/transactions') ?>",
        accounts: "<?= site_url('api/transactions/accounts') ?>"
    };

    const PAGE_SIZE = 50;
    let listPage = 0;
    let listPageSize = PAGE_SIZE;
    let listLoading = false;
    let suppressPageEvent = false;
    let accounts = [];

    const gridSource = {
        localdata: [],
        datatype: "array",
        totalrecords: 0,
        datafields: [
            { name: "id", type: "number" },
            { name: "transaction_date", type: "string" },
            { name: "reference_no", type: "string" },
            { name: "account_code", type: "string" },
            { name: "account_name", type: "string" },
            { name: "account_type", type: "string" },
            { name: "description", type: "string" },
            { name: "debit", type: "number" },
            { name: "credit", type: "number" },
            { name: "created_at", type: "string" }
        ]
    };
    let gridAdapter = null;

    function formatMoney(value) {
        const n = Number(value || 0);
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getDateFilterValue(selector) {
        const date = $(selector).jqxDateTimeInput("getDate");
        if (!date) {
            return "";
        }
        return $(selector).jqxDateTimeInput("getText");
    }

    function getFilterParams() {
        const params = {};
        const referenceNo = $("#referenceNoFilter").val().trim();
        const checkedAccounts = $("#accountCodeFilter").jqxDropDownList("getCheckedItems") || [];
        const accountCodes = checkedAccounts
            .map(function (item) { return String(item.value || "").trim(); })
            .filter(function (code) { return code !== ""; });
        const dateFrom = getDateFilterValue("#dateFromFilter");
        const dateTo = getDateFilterValue("#dateToFilter");

        if (referenceNo) {
            params.reference_no = referenceNo;
        }
        if (accountCodes.length > 0) {
            params.account_code = accountCodes;
        }
        if (dateFrom) {
            params.date_from = dateFrom;
        }
        if (dateTo) {
            params.date_to = dateTo;
        }
        return params;
    }

    function updateSummary(summary, total) {
        $("#metricTotalBalance").text(formatMoney(summary?.total_balance));
        $("#metricTotalDebit").text(formatMoney(summary?.total_debit));
        $("#metricTotalCredit").text(formatMoney(summary?.total_credit));
        $("#metricTotalRows").text(String(total || 0));

        const row = $("#accountBalancesRow");
        row.empty();
        (summary?.accounts || []).forEach(function (account) {
            const label = account.name
                ? `${account.code} - ${account.name}`
                : account.code;
            row.append(
                `<div class="col-6 col-md-3">
                    <span class="text-secondary">${label}</span>
                    <div class="fw-semibold">${formatMoney(account.balance)}</div>
                </div>`
            );
        });
    }

    function updateGridSource(rows, total) {
        suppressPageEvent = true;
        gridSource.localdata = rows;
        gridSource.totalrecords = total;
        gridAdapter.dataBind();
        $("#transactionsGrid").jqxGrid("updatebounddata");
        window.setTimeout(function () {
            suppressPageEvent = false;
        }, 0);
    }

    function loadTransactions(page, perPage, resetPage) {
        if (listLoading) {
            return;
        }

        if (resetPage) {
            listPage = 0;
        } else if (page !== undefined && page !== null) {
            listPage = page;
        }
        if (perPage !== undefined && perPage !== null) {
            listPageSize = perPage;
        }

        listLoading = true;
        $("#filterError").text("");

        const params = getFilterParams();
        params.page = listPage + 1;
        params.per_page = listPageSize;

        $.ajax({
            url: API_URLS.transactions,
            data: params,
            dataType: "json"
        })
            .done(function (res) {
                const rows = res.data || [];
                const total = Number(res.meta?.total || 0);
                listPage = Math.max(0, Number(res.meta?.page || 1) - 1);
                listPageSize = Number(res.meta?.per_page || listPageSize);
                updateSummary(res.summary || {}, total);
                updateGridSource(rows, total);
            })
            .fail(function (xhr) {
                $("#filterError").text(xhr.responseJSON?.message || "Failed to load transactions.");
            })
            .always(function () {
                listLoading = false;
            });
    }

    function clearAccountFilter() {
        const items = $("#accountCodeFilter").jqxDropDownList("getItems") || [];
        items.forEach(function (_item, index) {
            $("#accountCodeFilter").jqxDropDownList("uncheckIndex", index);
        });
    }

    function loadAccounts() {
        return $.getJSON(API_URLS.accounts).then(function (res) {
            accounts = (res.data || []).map(function (row) {
                return {
                    label: `${row.code} - ${row.name}`,
                    value: row.code
                };
            });
            $("#accountCodeFilter").jqxDropDownList({
                source: accounts,
                displayMember: "label",
                valueMember: "value",
                width: "100%",
                height: 34,
                placeHolder: "Select accounts",
                checkboxes: true
            });
        });
    }

    $(function () {
        $("#dateFromFilter").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });
        $("#dateToFilter").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true });
        $("#applyFiltersBtn").jqxButton({ height: 34, theme: "base" });
        $("#clearFiltersBtn").jqxButton({ height: 34, theme: "base" });

        gridAdapter = new $.jqx.dataAdapter(gridSource);

        $("#transactionsGrid").jqxGrid({
            width: "100%",
            height: 560,
            source: gridAdapter,
            pageable: true,
            pagesize: PAGE_SIZE,
            pagesizeoptions: ["20", "50", "100"],
            columnsresize: true,
            columns: [
                { text: "Date", datafield: "transaction_date", width: 110 },
                { text: "Reference", datafield: "reference_no", width: 180 },
                { text: "Account", datafield: "account_code", width: 90 },
                { text: "Account Name", datafield: "account_name", width: 180 },
                { text: "Description", datafield: "description", width: 260 },
                {
                    text: "Debit",
                    datafield: "debit",
                    width: 110,
                    cellsalign: "right",
                    cellsformat: "f2"
                },
                {
                    text: "Credit",
                    datafield: "credit",
                    width: 110,
                    cellsalign: "right",
                    cellsformat: "f2"
                },
                { text: "Created", datafield: "created_at", width: 160 }
            ]
        });

        $("#transactionsGrid").on("pagechanged pagesizechanged", function () {
            if (suppressPageEvent) {
                return;
            }
            const paging = $("#transactionsGrid").jqxGrid("getpaginginformation");
            loadTransactions(paging.pagenum, paging.pagesize);
        });

        loadAccounts().always(function () {
            loadTransactions(0, PAGE_SIZE, true);
        });

        $("#applyFiltersBtn").on("click", function () {
            loadTransactions(0, listPageSize, true);
        });

        $("#clearFiltersBtn").on("click", function () {
            $("#referenceNoFilter").val("");
            clearAccountFilter();
            $("#dateFromFilter").jqxDateTimeInput("setDate", null);
            $("#dateToFilter").jqxDateTimeInput("setDate", null);
            loadTransactions(0, PAGE_SIZE, true);
        });
    });
</script>
<?= $this->endSection() ?>
