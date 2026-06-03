<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Transactions<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4 px-5">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Transactions</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-2">
                    <!-- <label class="form-label small text-secondary mb-1">Reference No</label> -->
                    <input type="text" id="referenceNoFilter" class="form-control" placeholder="Search by reference no">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center gap-2">
                    <label class="form-label small text-secondary mb-1">Accounts:</label>
                    <div id="accountCodeFilter"></div>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                    <div>
                        <!-- <label class="form-label small text-secondary mb-1 d-block">From</label> -->
                        <div id="dateFromFilter"></div>
                    </div>
                    <span class="text-secondary pb-2">~</span>
                    <div>
                        <!-- <label class="form-label small text-secondary mb-1 d-block">To</label> -->
                        <div id="dateToFilter"></div>
                    </div>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2 justify-content-md-end flex-wrap">
                    <button type="button" id="newTransactionBtn" class="btn btn-success btn-sm">New Transaction</button>
                    <button type="button" id="applyFiltersBtn" class="btn btn-primary btn-sm">Search</button>
                    <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary btn-sm">Clear</button>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                    <span class="text-secondary small">View</span>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="accountGroupFilter" id="accountGroupAll" value="" checked>
                        <label class="form-check-label small" for="accountGroupAll">All</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="accountGroupFilter" id="accountGroupCapital" value="capital">
                        <label class="form-check-label small" for="accountGroupCapital">Capital</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="accountGroupFilter" id="accountGroupInventory" value="inventory">
                        <label class="form-check-label small" for="accountGroupInventory">Inventory</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="accountGroupFilter" id="accountGroupProfit" value="profit">
                        <label class="form-check-label small" for="accountGroupProfit">Profit</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="accountGroupFilter" id="accountGroupBusinessProfit" value="business_profit">
                        <label class="form-check-label small" for="accountGroupBusinessProfit">Business Profit</label>
                    </div>
                </div>
            </div>

            <div id="summaryPanel" class="border rounded bg-light p-3 mb-3 small">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Current Balance</span>
                        <div id="metricTotalBalance" class="fw-bold fs-5 text-primary">0.00</div>
                        <div id="metricCurrencyTotals" class="text-secondary mt-1"></div>
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
                <div id="businessProfitPanel" class="row g-2 mt-2 pt-2 border-top d-none">
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Sales Revenue</span>
                        <div id="metricSalesRevenue" class="fw-semibold" style="color:#027a48;">0.00</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Cost of Goods</span>
                        <div id="metricCostOfGoods" class="fw-semibold" style="color:#b42318;">0.00</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Transfer Fees</span>
                        <div id="metricTransferFees" class="fw-semibold" style="color:#b42318;">0.00</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-secondary">Net Business Profit</span>
                        <div id="metricNetBusinessProfit" class="fw-bold fs-5 text-primary">0.00</div>
                    </div>
                </div>
                <div id="filterError" class="text-danger mt-2"></div>
            </div>

            <div id="transactionsGrid"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="newTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newTransactionForm" class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Type</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="entryType" id="entryTypeExpense" value="expense" checked>
                                <label class="form-check-label" for="entryTypeExpense">Expense</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="entryType" id="entryTypeRevenue" value="revenue">
                                <label class="form-check-label" for="entryTypeRevenue">Revenue</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="entryType" id="entryTypeSwap" value="swap">
                                <label class="form-check-label" for="entryTypeSwap">Swap</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Date</label>
                        <div id="newTransactionDate"></div>
                    </div>
                    <div class="col-12 col-md-6" id="newTransactionAmountGroup">
                        <label class="form-label">
                            Amount
                            <span id="newTransactionRateLink" class="text-primary ms-2 d-none" role="button" tabindex="0"></span>
                        </label>
                        <input type="number" id="newTransactionAmount" class="form-control" min="0.01" step="0.01">
                        <div class="form-text" id="newTransactionAmountHint">In payment account currency</div>
                        <div id="newTransactionUsdHint" class="form-text text-muted d-none"></div>
                    </div>
                    <div class="col-12" id="expenseRevenueFields">
                        <label class="form-label">Account</label>
                        <select id="newTransactionAccount" class="form-select">
                            <option value="">Select account</option>
                        </select>
                    </div>
                    <div class="col-12" id="expenseRevenuePaymentField">
                        <label class="form-label">Payment Method</label>
                        <select id="newTransactionPaymentMethod" class="form-select">
                            <option value="">Select payment method</option>
                        </select>
                    </div>
                    <div id="swapFields" class="col-12 d-none">
                        <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">From (payment leaves)</label>
                            <select id="swapFromPaymentMethod" class="form-select">
                                <option value="">Select payment method</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">To (payment receives)</label>
                            <select id="swapToPaymentMethod" class="form-select">
                                <option value="">Select payment method</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">
                                From amount
                                <span id="swapFromRateLink" class="text-primary ms-2 d-none" role="button" tabindex="0"></span>
                            </label>
                            <input type="number" id="swapFromAmount" class="form-control" min="0.01" step="0.01">
                            <div class="form-text" id="swapFromAmountHint"></div>
                        </div>
                        <div class="col-12 col-md-6" id="swapToAmountGroup">
                            <label class="form-label">
                                To amount
                                <span id="swapToRateLink" class="text-primary ms-2 d-none" role="button" tabindex="0"></span>
                            </label>
                            <input type="number" id="swapToAmount" class="form-control" min="0.01" step="0.01">
                            <div class="form-text" id="swapToAmountHint"></div>
                        </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea id="newTransactionDescription" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>
                    <div class="col-12">
                        <div id="newTransactionMessage" class="small fw-semibold"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveNewTransactionBtn" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="txnExchangeRateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exchange Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="txnExchangeRateInput" class="form-label text-secondary mb-1">1 USD = ?</label>
                <div class="input-group">
                    <input type="number" id="txnExchangeRateInput" class="form-control" min="0" step="any" placeholder="0.00">
                    <span class="input-group-text" id="txnExchangeRateCurrencyCode">CNY</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTxnExchangeRateBtn">Save</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<?php
use Config\Accounting;
$accountingConfig = config(Accounting::class);
?>
<script>
    const API_URLS = {
        transactions: "<?= site_url('api/transactions') ?>",
        accounts: "<?= site_url('api/transactions/accounts') ?>",
        paymentMethods: "<?= site_url('api/payment-methods') ?>",
        exchangeRates: "<?= site_url('api/exchange-rates') ?>"
    };

    const BASE_CURRENCY = "USD";
    const INVENTORY_ACCOUNT_CODE = "<?= esc($accountingConfig->inventoryAccount, 'js') ?>";
    const SALES_REVENUE_ACCOUNT_CODE = "<?= esc($accountingConfig->salesRevenueAccount, 'js') ?>";
    const COGS_ACCOUNT_CODE = "<?= esc($accountingConfig->cogsAccount, 'js') ?>";

    const PAGE_SIZE = 50;
    let listPage = 0;
    let listPageSize = PAGE_SIZE;
    let listLoading = false;
    let suppressPageEvent = false;
    let accounts = [];
    let accountsData = [];
    let paymentMethods = [];
    let newTransactionModal = null;
    let txnExchangeRateModal = null;
    let exchangeRatesByCurrency = {};
    let txnRateEditTarget = "payment";
    let swapToAmountManual = false;
    let suppressAccountGroupReset = false;

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
            { name: "original_amount", type: "number" },
            { name: "currency", type: "string" },
            { name: "exchange_rate", type: "number" },
            { name: "created_at", type: "string" }
        ]
    };
    let gridAdapter = null;

    function formatMoney(value) {
        const n = Number(value || 0);
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function isZeroAmount(value) {
        return Math.abs(Number(value || 0)) < 0.005;
    }

    function formatRate(value) {
        const n = Number(value || 0);
        return n.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
    }

    function renderAmountCell(value, align, color, showAsNegative) {
        let text = "";
        if (!isZeroAmount(value)) {
            text = formatMoney(value);
            if (showAsNegative) {
                text = "-" + text;
            }
        }
        let style = "margin:6px 4px;text-align:" + (align || "right") + ";";
        if (color) {
            style += "color:" + color + ";font-weight:600;";
        }
        return '<div style="' + style + '">' + text + "</div>";
    }

    function renderRateCell(value) {
        const text = isZeroAmount(value) ? "" : formatRate(value);
        return '<div style="margin:6px 4px;text-align:right;">' + text + "</div>";
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
        const accountGroup = String($('input[name="accountGroupFilter"]:checked').val() || "");
        if (accountGroup) {
            params.account_group = accountGroup;
        }
        return params;
    }

    function updateSummary(summary, total) {
        $("#metricTotalBalance").text(formatMoney(summary?.total_balance));
        $("#metricTotalDebit").text(formatMoney(summary?.total_debit));
        $("#metricTotalCredit").text(formatMoney(summary?.total_credit));
        $("#metricTotalRows").text(String(total || 0));

        const currencyTotals = $("#metricCurrencyTotals");
        currencyTotals.empty();
        (summary?.currency_totals || []).forEach(function (row) {
            currencyTotals.append(
                `<div>${row.currency} ${formatMoney(row.total_original)}</div>`
            );
        });

        const row = $("#accountBalancesRow");
        row.empty();
        (summary?.accounts || []).forEach(function (account) {
            const label = account.name
                ? `${account.code} - ${account.name}`
                : account.code;
            const cur = String(account.currency_code || BASE_CURRENCY).toUpperCase();
            let balanceHtml;
            if (cur !== BASE_CURRENCY) {
                balanceHtml =
                    `<div class="fw-semibold">${formatMoney(account.balance_original)} ${cur}</div>` +
                    `<div class="text-secondary small">${formatMoney(account.balance)} USD</div>`;
            } else {
                balanceHtml = `<div class="fw-semibold">${formatMoney(account.balance)}</div>`;
            }
            row.append(
                `<div class="col-6 col-md-3">
                    <span class="text-secondary">${label}</span>
                    ${balanceHtml}
                </div>`
            );
        });

        const bp = summary?.business_profit;
        const $bpPanel = $("#businessProfitPanel");
        if (bp) {
            $bpPanel.removeClass("d-none");
            $("#metricSalesRevenue").text(formatMoney(bp.sales_revenue));
            $("#metricCostOfGoods").text(formatMoney(bp.cost_of_goods));
            $("#metricTransferFees").text(formatMoney(bp.transfer_fees));
            $("#metricNetBusinessProfit").text(formatMoney(bp.net_profit));
        } else {
            $bpPanel.addClass("d-none");
        }
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

    function getAccountCodesForGroup(group) {
        const key = String(group || "").toLowerCase();
        if (!key) {
            return [];
        }

        return accountsData
            .filter(function (row) {
                const type = String(row.account_type || "").toUpperCase();
                const code = String(row.code || "");
                if (key === "inventory") {
                    return code === INVENTORY_ACCOUNT_CODE;
                }
                if (key === "capital") {
                    return (type === "ASSET" && code !== INVENTORY_ACCOUNT_CODE) || type === "EQUITY";
                }
                if (key === "profit") {
                    return type === "REVENUE" || type === "EXPENSE";
                }
                if (key === "business_profit") {
                    return code === SALES_REVENUE_ACCOUNT_CODE || code === COGS_ACCOUNT_CODE;
                }
                return false;
            })
            .map(function (row) { return String(row.code); });
    }

    function setAccountFilterByCodes(codes) {
        clearAccountFilter();
        const dropdown = $("#accountCodeFilter");
        const items = dropdown.jqxDropDownList("getItems") || [];
        const codeSet = new Set(codes);
        items.forEach(function (item, index) {
            if (codeSet.has(String(item.value || ""))) {
                dropdown.jqxDropDownList("checkIndex", index);
            }
        });
    }

    function applyAccountGroupFilter(group, reload) {
        suppressAccountGroupReset = true;
        if (!group) {
            clearAccountFilter();
        } else {
            setAccountFilterByCodes(getAccountCodesForGroup(group));
        }
        suppressAccountGroupReset = false;
        if (reload) {
            loadTransactions(0, listPageSize, true);
        }
    }

    function clearAccountGroupFilter() {
        $("#accountGroupAll").prop("checked", true);
    }

    function loadAccounts() {
        return $.getJSON(API_URLS.accounts).then(function (res) {
            accountsData = res.data || [];
            accounts = accountsData.map(function (row) {
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

    function setNewTransactionMessage(message, isError) {
        const box = $("#newTransactionMessage");
        box.text(message || "");
        box.removeClass("text-success text-danger");
        if (message) {
            box.addClass(isError ? "text-danger" : "text-success");
        }
    }

    function getSelectedEntryType() {
        return String($('input[name="entryType"]:checked').val() || "expense");
    }

    function paymentMethodOptionLabel(row) {
        const cur = String(row.account_currency || BASE_CURRENCY).toUpperCase();
        return row.account_code
            ? `${row.name || row.code} (${row.account_code}, ${cur})`
            : (row.name || row.code);
    }

    function fillPaymentMethodSelect(select) {
        select.find("option:not(:first)").remove();
        paymentMethods.forEach(function (row) {
            select.append(`<option value="${row.code}">${paymentMethodOptionLabel(row)}</option>`);
        });
    }

    function loadModalAccounts() {
        updateEntryTypeUi();
        if (getSelectedEntryType() === "swap") {
            return $.Deferred().resolve().promise();
        }

        const accountType = getSelectedEntryType() === "expense" ? "EXPENSE" : "REVENUE";
        const select = $("#newTransactionAccount");
        const current = select.val();
        select.find("option:not(:first)").remove();

        return $.getJSON(API_URLS.accounts, { account_type: accountType }).done(function (res) {
            (res.data || []).forEach(function (row) {
                select.append(
                    `<option value="${row.code}">${row.code} — ${row.name || ""}</option>`
                );
            });
            if (current && select.find(`option[value="${current}"]`).length) {
                select.val(current);
            }
        });
    }

    function loadPaymentMethods() {
        return $.getJSON(API_URLS.paymentMethods).done(function (res) {
            paymentMethods = (res.data || []).filter(function (row) {
                return Number(row.is_active) === 1 && row.account_id;
            });
            fillPaymentMethodSelect($("#newTransactionPaymentMethod"));
            fillPaymentMethodSelect($("#swapFromPaymentMethod"));
            fillPaymentMethodSelect($("#swapToPaymentMethod"));
        });
    }

    function updateEntryTypeUi() {
        const isSwap = getSelectedEntryType() === "swap";
        $("#expenseRevenueFields, #expenseRevenuePaymentField, #newTransactionAmountGroup")
            .toggleClass("d-none", isSwap);
        $("#swapFields").toggleClass("d-none", !isSwap);
        if (isSwap) {
            updateSwapCurrencyUi();
        } else {
            updatePaymentCurrencyUi();
        }
    }

    function getPaymentMethodByCode(code) {
        const key = String(code || "");
        return paymentMethods.find(function (row) { return row.code === key; }) || null;
    }

    function getSwapFromMethod() {
        return getPaymentMethodByCode($("#swapFromPaymentMethod").val());
    }

    function getSwapToMethod() {
        return getPaymentMethodByCode($("#swapToPaymentMethod").val());
    }

    function getSwapFromCurrency() {
        return String(getSwapFromMethod()?.account_currency || BASE_CURRENCY).toUpperCase();
    }

    function getSwapToCurrency() {
        return String(getSwapToMethod()?.account_currency || BASE_CURRENCY).toUpperCase();
    }

    function amountToUsd(amount, currency) {
        if (currency === BASE_CURRENCY) {
            return amount;
        }
        const rate = getExchangeRateForCurrency(currency);
        return rate > 0 ? amount / rate : 0;
    }

    function usdToAmount(usd, currency) {
        if (currency === BASE_CURRENCY) {
            return usd;
        }
        const rate = getExchangeRateForCurrency(currency);
        return rate > 0 ? usd * rate : 0;
    }

    function recalcSwapToAmount() {
        if (swapToAmountManual) {
            return;
        }
        const fromAmount = Number($("#swapFromAmount").val() || 0);
        if (fromAmount <= 0) {
            $("#swapToAmount").val("");
            return;
        }
        const fromCur = getSwapFromCurrency();
        const toCur = getSwapToCurrency();
        const usd = amountToUsd(fromAmount, fromCur);
        if (usd <= 0) {
            return;
        }
        const toAmount = usdToAmount(usd, toCur);
        if (toAmount > 0) {
            $("#swapToAmount").val(toAmount.toFixed(2));
        }
    }

    function updateSwapSideRateUi(currency, $rateLink, $hint, prefix) {
        if (currency === BASE_CURRENCY) {
            $rateLink.addClass("d-none").text("");
            $hint.text(prefix + " in USD");
            return;
        }
        const rate = getExchangeRateForCurrency(currency);
        $hint.text(prefix + " in " + currency);
        $rateLink
            .removeClass("d-none")
            .text(rate > 0 ? "1 USD = " + rate + " " + currency : "1 USD = ? " + currency);
    }

    function updateSwapCurrencyUi() {
        const fromCur = getSwapFromCurrency();
        const toCur = getSwapToCurrency();
        updateSwapSideRateUi(fromCur, $("#swapFromRateLink"), $("#swapFromAmountHint"), "From amount");
        updateSwapSideRateUi(toCur, $("#swapToRateLink"), $("#swapToAmountHint"), "To amount");
        const sameCurrency = fromCur === toCur && fromCur !== "";
        if (sameCurrency) {
            $("#swapToAmountGroup").addClass("d-none");
            const fromAmount = Number($("#swapFromAmount").val() || 0);
            if (fromAmount > 0) {
                $("#swapToAmount").val(fromAmount.toFixed(2));
            }
        } else {
            $("#swapToAmountGroup").removeClass("d-none");
            recalcSwapToAmount();
        }
    }

    function loadExchangeRatesForSwap() {
        const currencies = [getSwapFromCurrency(), getSwapToCurrency()]
            .filter(function (c) { return c && c !== BASE_CURRENCY; });
        const unique = currencies.filter(function (c, i, arr) { return arr.indexOf(c) === i; });
        if (!unique.length) {
            updateSwapCurrencyUi();
            return $.Deferred().resolve().promise();
        }
        const requests = unique.map(function (currency) {
            return $.getJSON(API_URLS.exchangeRates + "/latest/" + encodeURIComponent(currency))
                .done(function (res) {
                    const rate = Number(res.data?.rate || 0);
                    if (rate > 0) {
                        exchangeRatesByCurrency[currency] = rate;
                    } else {
                        delete exchangeRatesByCurrency[currency];
                    }
                });
        });
        return $.when.apply($, requests).always(updateSwapCurrencyUi);
    }

    function getTxnRateEditCurrency() {
        if (txnRateEditTarget === "swapFrom") {
            return getSwapFromCurrency();
        }
        if (txnRateEditTarget === "swapTo") {
            return getSwapToCurrency();
        }
        return getPaymentCurrency();
    }

    function getSelectedPaymentMethod() {
        const code = String($("#newTransactionPaymentMethod").val() || "");
        return paymentMethods.find(function (row) { return row.code === code; }) || null;
    }

    function getPaymentCurrency() {
        const method = getSelectedPaymentMethod();
        return String(method?.account_currency || BASE_CURRENCY).toUpperCase();
    }

    function getExchangeRateForCurrency(code) {
        const currency = String(code || "").toUpperCase();
        if (currency === BASE_CURRENCY) {
            return 1;
        }
        return Number(exchangeRatesByCurrency[currency] || 0);
    }

    function updatePaymentCurrencyUi() {
        const currency = getPaymentCurrency();
        const amount = Number($("#newTransactionAmount").val() || 0);
        const $rateLink = $("#newTransactionRateLink");
        const $usdHint = $("#newTransactionUsdHint");

        if (currency === BASE_CURRENCY) {
            $("#newTransactionAmountHint").text("Amount in USD");
            $rateLink.addClass("d-none").text("");
            $usdHint.addClass("d-none").text("");
            return;
        }

        $("#newTransactionAmountHint").text("Amount in " + currency);
        const rate = getExchangeRateForCurrency(currency);
        $rateLink
            .removeClass("d-none")
            .text(rate > 0 ? "1 USD = " + rate + " " + currency : "1 USD = ? " + currency);

        if (rate > 0 && amount > 0) {
            $usdHint.removeClass("d-none").text("≈ $" + (amount / rate).toFixed(2) + " USD (ledger debit/credit)");
        } else {
            $usdHint.addClass("d-none").text("");
        }
    }

    function loadExchangeRateForPaymentCurrency() {
        const currency = getPaymentCurrency();
        if (currency === BASE_CURRENCY || currency === "") {
            updatePaymentCurrencyUi();
            return $.Deferred().resolve().promise();
        }

        return $.getJSON(API_URLS.exchangeRates + "/latest/" + encodeURIComponent(currency))
            .done(function (res) {
                const rate = Number(res.data?.rate || 0);
                if (rate > 0) {
                    exchangeRatesByCurrency[currency] = rate;
                } else {
                    delete exchangeRatesByCurrency[currency];
                }
                updatePaymentCurrencyUi();
            })
            .fail(function () {
                updatePaymentCurrencyUi();
            });
    }

    function openTxnExchangeRateModal(target) {
        txnRateEditTarget = target || "payment";
        const currency = getTxnRateEditCurrency();
        if (currency === BASE_CURRENCY) {
            return;
        }
        $("#txnExchangeRateCurrencyCode").text(currency);
        const loadPromise = txnRateEditTarget === "payment"
            ? loadExchangeRateForPaymentCurrency()
            : loadExchangeRatesForSwap();
        loadPromise.always(function () {
            $("#txnExchangeRateInput").val(getExchangeRateForCurrency(currency) || "");
            txnExchangeRateModal.show();
        });
    }

    function saveTxnExchangeRateFromModal() {
        const currency = getTxnRateEditCurrency();
        const rate = Number($("#txnExchangeRateInput").val() || 0);
        if (currency === BASE_CURRENCY || rate <= 0) {
            setNewTransactionMessage("Exchange rate must be greater than 0.", true);
            return;
        }

        $("#saveTxnExchangeRateBtn").prop("disabled", true);
        $.ajax({
            url: API_URLS.exchangeRates,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                quote_currency: currency,
                rate: rate
            })
        }).done(function () {
            exchangeRatesByCurrency[currency] = rate;
            txnExchangeRateModal.hide();
            setNewTransactionMessage("");
            if (txnRateEditTarget === "payment") {
                updatePaymentCurrencyUi();
            } else {
                swapToAmountManual = false;
                updateSwapCurrencyUi();
            }
        }).fail(function (xhr) {
            setNewTransactionMessage(xhr.responseJSON?.message || "Failed to save exchange rate.", true);
        }).always(function () {
            $("#saveTxnExchangeRateBtn").prop("disabled", false);
        });
    }

    function resetNewTransactionForm() {
        $('input[name="entryType"][value="expense"]').prop("checked", true);
        $("#newTransactionDate").jqxDateTimeInput("setDate", new Date());
        $("#newTransactionAmount").val("");
        $("#newTransactionDescription").val("");
        $("#newTransactionPaymentMethod").val("");
        $("#swapFromPaymentMethod, #swapToPaymentMethod").val("");
        $("#swapFromAmount, #swapToAmount").val("");
        swapToAmountManual = false;
        setNewTransactionMessage("");
        loadModalAccounts();
    }

    function openNewTransactionModal() {
        resetNewTransactionForm();
        newTransactionModal.show();
    }

    function saveNewTransaction() {
        const entryType = getSelectedEntryType();
        const transactionDate = $("#newTransactionDate").jqxDateTimeInput("getText");
        const description = String($("#newTransactionDescription").val() || "").trim();

        if (!transactionDate) {
            setNewTransactionMessage("Transaction date is required.", true);
            return;
        }

        let payload;

        if (entryType === "swap") {
            const fromMethod = String($("#swapFromPaymentMethod").val() || "").trim();
            const toMethod = String($("#swapToPaymentMethod").val() || "").trim();
            const fromAmount = Number($("#swapFromAmount").val() || 0);
            const toAmount = Number($("#swapToAmount").val() || 0);
            const fromCur = getSwapFromCurrency();
            const toCur = getSwapToCurrency();

            if (!fromMethod || !toMethod) {
                setNewTransactionMessage("Please select from and to payment methods.", true);
                return;
            }
            if (fromMethod === toMethod) {
                setNewTransactionMessage("From and to payment methods must be different.", true);
                return;
            }
            if (fromAmount <= 0 || toAmount <= 0) {
                setNewTransactionMessage("From and to amounts must be greater than 0.", true);
                return;
            }
            if (fromCur !== BASE_CURRENCY && getExchangeRateForCurrency(fromCur) <= 0) {
                setNewTransactionMessage("Set an exchange rate for " + fromCur + " before saving.", true);
                return;
            }
            if (toCur !== BASE_CURRENCY && getExchangeRateForCurrency(toCur) <= 0) {
                setNewTransactionMessage("Set an exchange rate for " + toCur + " before saving.", true);
                return;
            }

            payload = {
                entry_type: "swap",
                from_payment_method: fromMethod,
                to_payment_method: toMethod,
                amount: fromAmount,
                to_amount: toAmount,
                transaction_date: transactionDate,
                description: description
            };
            if (fromCur !== BASE_CURRENCY) {
                payload.from_exchange_rate = getExchangeRateForCurrency(fromCur);
            }
            if (toCur !== BASE_CURRENCY) {
                payload.to_exchange_rate = getExchangeRateForCurrency(toCur);
            }
        } else {
            const accountCode = String($("#newTransactionAccount").val() || "").trim();
            const paymentMethod = String($("#newTransactionPaymentMethod").val() || "").trim();
            const amount = Number($("#newTransactionAmount").val() || 0);

            if (!accountCode) {
                setNewTransactionMessage("Please select an account.", true);
                return;
            }
            if (!paymentMethod) {
                setNewTransactionMessage("Please select a payment method.", true);
                return;
            }
            if (amount <= 0) {
                setNewTransactionMessage("Amount must be greater than 0.", true);
                return;
            }

            const paymentCurrency = getPaymentCurrency();
            const exchangeRate = getExchangeRateForCurrency(paymentCurrency);
            if (paymentCurrency !== BASE_CURRENCY && exchangeRate <= 0) {
                setNewTransactionMessage("Set an exchange rate for " + paymentCurrency + " before saving.", true);
                return;
            }

            payload = {
                entry_type: entryType,
                account_code: accountCode,
                payment_method: paymentMethod,
                amount: amount,
                transaction_date: transactionDate,
                description: description
            };
            if (paymentCurrency !== BASE_CURRENCY) {
                payload.exchange_rate = exchangeRate;
            }
        }

        $("#saveNewTransactionBtn").prop("disabled", true);
        setNewTransactionMessage("");

        $.ajax({
            url: API_URLS.transactions,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload)
        }).done(function (res) {
            setNewTransactionMessage(res.message || "Transaction saved.");
            loadTransactions(0, listPageSize, true);
            window.setTimeout(function () {
                newTransactionModal.hide();
            }, 400);
        }).fail(function (xhr) {
            setNewTransactionMessage(xhr.responseJSON?.message || "Failed to save transaction.", true);
        }).always(function () {
            $("#saveNewTransactionBtn").prop("disabled", false);
        });
    }

    $(function () {
        const firstDayOfMonth = new Date();
        firstDayOfMonth.setDate(1);
        firstDayOfMonth.setHours(0, 0, 0, 0);

        $("#dateFromFilter").jqxDateTimeInput({ width: 150, height: 34, formatString: "yyyy-MM-dd", allowNullDate: true, value: firstDayOfMonth });
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
                {
                    text: "#",
                    width: 55,
                    sortable: false,
                    filterable: false,
                    editable: false,
                    menu: false,
                    cellsrenderer: function (row) {
                        const n = listPage * listPageSize + row + 1;
                        return '<div style="margin:6px 4px;text-align:center;">' + n + "</div>";
                    }
                },
                { text: "Date", datafield: "transaction_date", width: 110 },
                { text: "Reference", datafield: "reference_no", width: 180 },
                { text: "Account", datafield: "account_code", width: 90 },
                { text: "Account Name", datafield: "account_name", width: 180 },
                { text: "Description", datafield: "description", width: 260 },
                {
                    text: "Original",
                    datafield: "original_amount",
                    width: 100,
                    cellsalign: "right",
                    cellsrenderer: function (_row, _col, value) {
                        return renderAmountCell(value, "right");
                    }
                },
                { text: "Curr.", datafield: "currency", width: 55 },
                {
                    text: "Rate",
                    datafield: "exchange_rate",
                    width: 80,
                    cellsalign: "right",
                    cellsrenderer: function (_row, _col, value) {
                        return renderRateCell(value);
                    }
                },
                {
                    text: "Debit (USD)",
                    datafield: "debit",
                    width: 110,
                    cellsalign: "right",
                    cellsrenderer: function (_row, _col, value) {
                        return renderAmountCell(value, "right", "#b42318");
                    }
                },
                {
                    text: "Credit (USD)",
                    datafield: "credit",
                    width: 110,
                    cellsalign: "right",
                    cellsrenderer: function (_row, _col, value) {
                        return renderAmountCell(value, "right", "#027a48", true);
                    }
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

        $("#newTransactionDate").jqxDateTimeInput({
            width: "100%",
            height: 34,
            formatString: "yyyy-MM-dd",
            value: new Date()
        });

        newTransactionModal = new bootstrap.Modal(document.getElementById("newTransactionModal"));
        txnExchangeRateModal = new bootstrap.Modal(document.getElementById("txnExchangeRateModal"));

        loadPaymentMethods();
        loadAccounts().always(function () {
            loadTransactions(0, PAGE_SIZE, true);
        });

        $("#newTransactionBtn").on("click", openNewTransactionModal);
        $("#saveNewTransactionBtn").on("click", saveNewTransaction);
        $("#newTransactionPaymentMethod").on("change", loadExchangeRateForPaymentCurrency);
        $("#newTransactionAmount").on("input", updatePaymentCurrencyUi);
        $("#newTransactionRateLink").on("click", function () { openTxnExchangeRateModal("payment"); });
        $("#swapFromRateLink").on("click", function () { openTxnExchangeRateModal("swapFrom"); });
        $("#swapToRateLink").on("click", function () { openTxnExchangeRateModal("swapTo"); });
        $("#saveTxnExchangeRateBtn").on("click", saveTxnExchangeRateFromModal);
        $('input[name="entryType"]').on("change", loadModalAccounts);
        $("#swapFromPaymentMethod, #swapToPaymentMethod").on("change", function () {
            swapToAmountManual = false;
            loadExchangeRatesForSwap();
        });
        $("#swapFromAmount").on("input", function () {
            swapToAmountManual = false;
            updateSwapCurrencyUi();
        });
        $("#swapToAmount").on("input", function () {
            swapToAmountManual = true;
        });

        $("#applyFiltersBtn").on("click", function () {
            loadTransactions(0, listPageSize, true);
        });

        $('input[name="accountGroupFilter"]').on("change", function () {
            applyAccountGroupFilter(String($('input[name="accountGroupFilter"]:checked').val() || ""), true);
        });

        $("#accountCodeFilter").on("checkChange", function () {
            if (suppressAccountGroupReset) {
                return;
            }
            clearAccountGroupFilter();
        });

        $("#clearFiltersBtn").on("click", function () {
            $("#referenceNoFilter").val("");
            clearAccountGroupFilter();
            clearAccountFilter();
            $("#dateFromFilter").jqxDateTimeInput("setDate", null);
            $("#dateToFilter").jqxDateTimeInput("setDate", null);
            loadTransactions(0, PAGE_SIZE, true);
        });
    });
</script>
<?= $this->endSection() ?>
