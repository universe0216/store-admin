<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>New Purchase<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    #purchaseHeaderForm .jqx-numberinput input,
    #purchaseItemForm .jqx-numberinput input,
    #discountTotalInput input,
    #paidAmountInput input {
        height: 100% !important;
        line-height: 34px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    .tag-chip-picker {
        min-height: 42px;
    }
    #purchaseTagsChips {
        min-height: 28px;
    }
    .tag-chip {
        font-size: 0.8125rem;
        font-weight: 500;
        padding: 0.35rem 0.5rem;
    }
    .tag-chip-remove {
        width: 0.5rem;
        height: 0.5rem;
        opacity: 0.85;
    }
    #unitPriceRateLink {
        cursor: pointer;
        font-weight: 500;
    }
    #unitPriceInput {
        min-width: 0;
    }
    .purchase-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.4rem 0;
    }
    .purchase-summary-row .summary-label {
        color: #6c757d;
    }
    .purchase-summary-row .summary-value {
        min-width: 140px;
        text-align: right;
    }
    .purchase-summary-row .summary-value input.form-control,
    .purchase-summary-row .summary-value .jqx-numberinput {
        text-align: right;
    }
    #purchaseSummaryForm .jqx-numberinput {
        width: 140px !important;
    }
    #purchaseSummaryForm hr {
        margin: 0.75rem 0;
        opacity: 0.15;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 fw-bold mb-0">New Purchase</h1>
                </div>
                <div id="purchaseHeaderForm" class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Supplier</label>
                        <div id="supplierDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Warehouse</label>
                        <div id="purchaseWarehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Purchase Date</label>
                        <div id="purchaseDate"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Payment Method</label>
                        <select id="paymentMethodSelect" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="check">Check</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div id="messageBox" class="small fw-semibold text-success mb-3"></div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Add Product</h2>
                        <form id="purchaseItemForm" class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-secondary mb-1">Name</label>
                                <input type="text" id="productNameInput" class="form-control" placeholder="Product name">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary mb-1">Serial Number</label>
                                <input type="text" id="productSerialInput" class="form-control" placeholder="Serial number">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary mb-1">Category</label>
                                <div id="categoryDropDownButton" style="width: 100%;">
                                    <div id="categoryTree"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Brand</label>
                                <input type="text" id="productBrandInput" class="form-control" placeholder="Brand">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Style</label>
                                <input type="text" id="productStyleInput" class="form-control" placeholder="Style">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Size</label>
                                <div id="sizeSelector"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Sets Count</label>
                                <div id="setsCountInput"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary mb-1">
                                    Unit Price
                                    <span id="unitPriceRateLink" class="text-primary ms-2 d-none" role="button" tabindex="0"></span>
                                </label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select id="unitPriceCurrency" class="form-select" style="max-width: 110px;">
                                        <option value="USD">USD</option>
                                    </select>
                                    <span id="unitPriceUsdHint" class="text-muted small text-nowrap d-none"></span>
                                    <input type="number" id="unitPriceInput" class="form-control flex-grow-1" min="0" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Total Units</label>
                                <div id="totalUnitsInput"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary mb-1">Total Price</label>
                                <div id="totalPriceInput"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary mb-1">Tags</label>
                                <div class="tag-chip-picker border rounded bg-white p-2">
                                    <div id="purchaseTagsChips" class="d-flex flex-wrap gap-1 mb-2"></div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <div id="purchaseTagsDropdown" class="flex-grow-1" style="min-width: 200px;"></div>
                                        <input type="text" id="newTagNameInput" class="form-control form-control-sm" placeholder="New tag name" style="max-width: 160px;">
                                        <button type="button" id="createTagBtn" class="btn btn-sm btn-outline-primary text-nowrap">Add Tag</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="button" id="addProductsBtn" class="btn btn-primary">+ Add Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Purchase Items (<span id="purchaseItemsCount">0</span>)</h2>
                        <div id="itemsGrid"></div>
                    </div>
                </div>

                <div id="purchaseSummaryForm" class="card shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Purchase Summary</h2>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Subtotal</span>
                            <div class="summary-value">
                                <input id="subTotalDisplay" type="text" class="form-control form-control-sm bg-light text-end" readonly value="0.00">
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Discount</span>
                            <div class="summary-value">
                                <div id="discountTotalInput"></div>
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Transfer Fee</span>
                            <div class="summary-value">
                                <div id="transferFeeInput"></div>
                            </div>
                        </div>
                        <hr>
                        <div class="purchase-summary-row">
                            <span class="summary-label fw-semibold text-dark">Grand Total</span>
                            <div class="summary-value">
                                <input id="grandTotalDisplay" type="text" class="form-control form-control-sm bg-light text-end fw-semibold" readonly value="0.00">
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Paid Amount</span>
                            <div class="summary-value">
                                <div id="paidAmountInput"></div>
                            </div>
                        </div>
                        <div class="purchase-summary-row">
                            <span class="summary-label">Total Paid (Paid + Transfer Fee)</span>
                            <div class="summary-value">
                                <input id="totalPaidDisplay" type="text" class="form-control form-control-sm bg-light text-end" readonly value="0.00">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-secondary mb-1">Notes</label>
                            <input id="notesInput" type="text" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= site_url('purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="button" id="savePurchaseBtn" class="btn btn-primary">Save Purchase</button>
        </div>
    </div>

    <div class="modal fade" id="exchangeRateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Exchange Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="exchangeRateInput" class="form-label text-secondary mb-1">
                        1 USD = ?
                    </label>
                    <div class="input-group">
                        <input type="number" id="exchangeRateInput" class="form-control" min="0" step="any" placeholder="0.00">
                        <span class="input-group-text" id="exchangeRateCurrencyCode">CNY</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveExchangeRateBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchaseConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">Total Units Count: <span id="confirmTotalUnitsCount" class="fw-semibold">0</span></div>
                    <div class="mb-2">Items Total: <span id="confirmTotalPriceSum" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Transfer Fee: <span id="confirmTransferFee" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Discount: <span id="confirmDiscount" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Paid Amount: <span id="confirmPaidAmount" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Grand Total: <span id="confirmGrandTotal" class="fw-semibold">0.00</span></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSavePurchaseBtn">Confirm Save</button>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            suppliers: "<?= site_url('api/suppliers') ?>",
            categories: "<?= site_url('api/categories') ?>",
            products: "<?= site_url('api/products') ?>",
            warehouses: "<?= site_url('api/warehouses') ?>",
            purchases: "<?= site_url('api/purchases') ?>",
            tags: "<?= site_url('api/tags') ?>",
            currencies: "<?= site_url('api/currencies') ?>",
            exchangeRates: "<?= site_url('api/exchange-rates') ?>"
        };

        const items = [];
        let suppliers = [];
        let categories = [];
        let warehouses = [];
        let currencies = [];
        let allTags = [];
        let selectedTagIds = new Set();
        let tagSyncLock = false;
        let selectedSizeCount = 0;
        let selectedCategoryId = 0;
        let confirmPurchaseModal = null;
        let exchangeRateModal = null;
        let exchangeRatesByCurrency = {};
        let totalsCalcLock = false;
        const FIXED_SIZES = [220, 225, 230, 235, 240, 245, 250].map(size => ({
            label: String(size),
            value: String(size)
        }));

        function getSelectedUnitCurrency() {
            return String($("#unitPriceCurrency").val() || "USD").toUpperCase();
        }

        function getExchangeRateForCurrency(code) {
            const currency = String(code || "").toUpperCase();
            if (currency === "USD") {
                return 1;
            }
            return Number(exchangeRatesByCurrency[currency] || 0);
        }

        function getUsdUnitPrice() {
            const currency = getSelectedUnitCurrency();
            const unitPrice = Number($("#unitPriceInput").val() || 0);
            if (currency === "USD") {
                return unitPrice;
            }

            const rate = getExchangeRateForCurrency(currency);
            if (rate <= 0) {
                return 0;
            }

            return Number((unitPrice / rate).toFixed(2));
        }

        function updateUnitPriceCurrencyUi() {
            const currency = getSelectedUnitCurrency();
            const $rateLink = $("#unitPriceRateLink");
            const $usdHint = $("#unitPriceUsdHint");

            if (currency === "USD") {
                $rateLink.addClass("d-none").text("");
                $usdHint.addClass("d-none").text("");
                return;
            }

            const rate = getExchangeRateForCurrency(currency);
            $rateLink.removeClass("d-none").text(rate > 0 ? `1 USD = ${rate} ${currency}` : "1 USD = ?");

            const usdUnit = getUsdUnitPrice();
            if (rate > 0) {
                $usdHint.removeClass("d-none").text(`$${usdUnit.toFixed(2)}`);
            } else {
                $usdHint.addClass("d-none").text("");
            }
        }

        function loadExchangeRateForCurrency(currency) {
            const code = String(currency || "").toUpperCase();
            if (code === "USD" || code === "") {
                updateUnitPriceCurrencyUi();
                return $.Deferred().resolve().promise();
            }

            return $.getJSON(`${API_URLS.exchangeRates}/latest/${encodeURIComponent(code)}`).done(function (res) {
                const rate = Number(res.data?.rate || 0);
                if (rate > 0) {
                    exchangeRatesByCurrency[code] = rate;
                } else {
                    delete exchangeRatesByCurrency[code];
                }
                updateUnitPriceCurrencyUi();
                recalcTotalPrice();
            }).fail(function (xhr) {
                $("#messageBox").text(xhr.responseJSON?.message || "Failed to load exchange rate.");
            });
        }

        function openExchangeRateModal() {
            const currency = getSelectedUnitCurrency();
            if (currency === "USD") {
                return;
            }

            $("#exchangeRateCurrencyCode").text(currency);
            loadExchangeRateForCurrency(currency).always(function () {
                $("#exchangeRateInput").val(getExchangeRateForCurrency(currency) || "");
                exchangeRateModal.show();
            });
        }

        function saveExchangeRateFromModal() {
            const currency = getSelectedUnitCurrency();
            const rate = Number($("#exchangeRateInput").val() || 0);
            if (currency === "USD" || rate <= 0) {
                $("#messageBox").text("Exchange rate must be greater than 0.");
                return;
            }

            $("#saveExchangeRateBtn").prop("disabled", true);
            $.ajax({
                url: API_URLS.exchangeRates,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    quote_currency: currency,
                    rate
                })
            }).done(function (res) {
                exchangeRatesByCurrency[currency] = rate;
                exchangeRateModal.hide();
                updateUnitPriceCurrencyUi();
                recalcTotalPrice();
                $("#messageBox").text(res.message || "Exchange rate saved.");
            }).fail(function (xhr) {
                $("#messageBox").text(xhr.responseJSON?.message || "Failed to save exchange rate.");
            }).always(function () {
                $("#saveExchangeRateBtn").prop("disabled", false);
            });
        }

        function loadCurrencies() {
            return $.getJSON(API_URLS.currencies).done(function (res) {
                currencies = res.data || [];
                const $select = $("#unitPriceCurrency");
                const current = $select.val();
                $select.empty();

                if (!currencies.some(row => String(row.code).toUpperCase() === "USD")) {
                    $select.append('<option value="USD">USD</option>');
                }

                currencies.forEach(function (row) {
                    $select.append(
                        $("<option></option>").attr("value", row.code).text(row.code)
                    );
                });

                $select.val(current || "USD");
                updateUnitPriceCurrencyUi();
            }).fail(function (xhr) {
                $("#messageBox").text(xhr.responseJSON?.message || "Failed to load currencies.");
            });
        }

        function initWidgets() {
            $("#supplierDropdown").jqxDropDownList({ width: "100%", height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select supplier" });
            $("#purchaseDate").jqxDateTimeInput({ width: "100%", height: 34, formatString: "yyyy-MM-dd" });
            $("#purchaseWarehouseDropdown").jqxDropDownList({ width: "100%", height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select warehouse" });
            $("#transferFeeInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 10,
                min: 0,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#discountTotalInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 12,
                min: -999999999,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#paidAmountInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 12,
                min: 0,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#categoryDropDownButton").jqxDropDownButton({ width: "100%", height: 34 });
            $("#categoryDropDownButton").jqxDropDownButton("setContent", '<div style="position: relative; margin-left: 3px; margin-top: 5px;" class="text-muted">Select category</div>');
            $("#sizeSelector").jqxDropDownList({
                width: "100%",
                height: 34,
                displayMember: "label",
                valueMember: "value",
                placeHolder: "Select size(s)",
                checkboxes: true,
                source: FIXED_SIZES
            });
            $("#setsCountInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 0,
                digits: 8,
                min: 0,
                inputMode: "simple",
                spinButtons: true,
                value: 0
            });
            $("#totalUnitsInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 0,
                digits: 10,
                min: 0,
                inputMode: "simple",
                spinButtons: false,
                readOnly: true,
                disabled: true,
                value: 0
            });
            $("#totalPriceInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 12,
                min: 0,
                inputMode: "simple",
                spinButtons: false,
                readOnly: true,
                disabled: true,
                value: 0
            });
            $("#savePurchaseBtn").jqxButton({ width: 160, height: 38, theme: "base" });
            $("#addProductsBtn").jqxButton({ width: 140, height: 34, theme: "base" });
            initTagsPicker();

            $("#itemsGrid").jqxGrid({
                width: "100%",
                height: 520,
                source: new $.jqx.dataAdapter({ localdata: items, datatype: "array" }),
                editable: true,
                editmode: "click",
                selectionmode: "singlerow",
                columnsresize: true,
                showtoolbar: true,
                showstatusbar: true,
                statusbarheight: 34,
                rendertoolbar: function (toolbar) {
                    const container = $('<div class="d-flex align-items-center h-100 px-2"></div>');
                    const deleteBtn = $('<button type="button" id="deleteSelectedGridRowBtn" class="btn btn-sm btn-outline-danger">Delete Selected Row</button>');
                    container.append(deleteBtn);
                    toolbar.append(container);
                    container.on("click", "#deleteSelectedGridRowBtn", removeSelectedRow);
                },
                renderstatusbar: function (statusbar) {
                    const footer = $('<div id="itemsGridTotalsFooter" class="d-flex align-items-center h-100 px-2 small fw-semibold text-secondary"></div>');
                    statusbar.append(footer);
                    updateGridFooterTotals();
                },
                columns: [
                    {
                        text: "Product",
                        datafield: "product_name",
                        width: 180,
                        editable: false
                    },
                    { text: "Product Number", datafield: "sku", width: 150, editable: false },
                    { text: "Brand", datafield: "brand", width: 120, editable: false },
                    { text: "Style", datafield: "style", width: 120, editable: false },
                    { text: "Warehouse", datafield: "warehouse_name", width: 140, editable: false },
                    { text: "Unit Cost", datafield: "unit_cost", width: 110, cellsformat: "f2", editable: false, cellsalign: "right" },
                    { text: "Size", datafield: "size_value", width: 120, editable: false },
                    { text: "Sets Count", datafield: "sets_count", width: 95, editable: false, cellsalign: "right" },
                    { text: "Units Count", datafield: "units_count", width: 95, editable: false, cellsalign: "right" },
                    { text: "Total Price", datafield: "total_price", width: 120, cellsformat: "f2", editable: false, cellsalign: "right" }
                ]
            });
        }

        function escapeHtml(text) {
            return String(text || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
        }

        function getSelectedTagIds() {
            return Array.from(selectedTagIds);
        }

        function renderTagChips() {
            const $container = $("#purchaseTagsChips").empty();
            allTags
                .filter(tag => selectedTagIds.has(Number(tag.id)))
                .forEach(tag => {
                    const bg = tag.color || "#0d6efd";
                    const chip = $(`
                        <span class="tag-chip badge d-inline-flex align-items-center gap-1 pe-2" data-tag-id="${Number(tag.id)}" style="background-color:${bg};">
                            ${escapeHtml(tag.name)}
                            <button type="button" class="tag-chip-remove btn-close btn-close-white" aria-label="Remove"></button>
                        </span>
                    `);
                    chip.find(".tag-chip-remove").on("click", function () {
                        removeSelectedTag(Number(tag.id));
                    });
                    $container.append(chip);
                });
        }

        function removeSelectedTag(tagId) {
            selectedTagIds.delete(Number(tagId));
            syncTagDropdownChecks();
            renderTagChips();
        }

        function addSelectedTag(tagId) {
            if (Number(tagId) > 0) {
                selectedTagIds.add(Number(tagId));
                syncTagDropdownChecks();
                renderTagChips();
            }
        }

        function syncTagDropdownChecks() {
            if (!$("#purchaseTagsDropdown").data("jqxDropDownList")) {
                return;
            }

            tagSyncLock = true;
            const items = $("#purchaseTagsDropdown").jqxDropDownList("getItems") || [];
            items.forEach((item, index) => {
                const id = Number(item.value);
                if (selectedTagIds.has(id)) {
                    $("#purchaseTagsDropdown").jqxDropDownList("checkIndex", index);
                } else {
                    $("#purchaseTagsDropdown").jqxDropDownList("uncheckIndex", index);
                }
            });
            tagSyncLock = false;
        }

        function initTagsPicker() {
            $("#purchaseTagsDropdown").jqxDropDownList({
                width: "100%",
                height: 34,
                displayMember: "name",
                valueMember: "id",
                placeHolder: "Select tags",
                checkboxes: true,
                source: []
            });

            $("#purchaseTagsDropdown").on("checkChange", function (event) {
                if (tagSyncLock) {
                    return;
                }

                const id = Number(event.args?.item?.value || 0);
                if (id < 1) {
                    return;
                }

                if (event.args.checked) {
                    selectedTagIds.add(id);
                } else {
                    selectedTagIds.delete(id);
                }
                renderTagChips();
            });
        }

        function loadTags() {
            return $.getJSON(API_URLS.tags).done(function (res) {
                allTags = res.data || [];
                $("#purchaseTagsDropdown").jqxDropDownList({ source: allTags });
                syncTagDropdownChecks();
                renderTagChips();
            }).fail(function (xhr) {
                $("#messageBox").text(xhr.responseJSON?.message || "Failed to load tags.");
            });
        }

        function createTagFromInput() {
            const name = String($("#newTagNameInput").val() || "").trim();
            if (name === "") {
                return;
            }

            const existing = allTags.find(t => String(t.name).toLowerCase() === name.toLowerCase());
            if (existing) {
                addSelectedTag(Number(existing.id));
                $("#newTagNameInput").val("");
                return;
            }

            $("#createTagBtn").prop("disabled", true);
            $.ajax({
                url: API_URLS.tags,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({ name })
            }).done(function (res) {
                const tag = res.data;
                if (!tag?.id) {
                    return;
                }

                allTags.push(tag);
                allTags.sort((a, b) => String(a.name).localeCompare(String(b.name)));
                $("#purchaseTagsDropdown").jqxDropDownList({ source: allTags });
                addSelectedTag(Number(tag.id));
                $("#newTagNameInput").val("");
            }).fail(function (xhr) {
                $("#messageBox").text(xhr.responseJSON?.message || "Failed to create tag.");
            }).always(function () {
                $("#createTagBtn").prop("disabled", false);
            });
        }

        function buildCategoryTreeItems(rows) {
            const byParent = new Map();
            rows.forEach(row => {
                const parentId = row.parent_id ? Number(row.parent_id) : 0;
                if (!byParent.has(parentId)) {
                    byParent.set(parentId, []);
                }
                byParent.get(parentId).push(row);
            });

            const makeItems = (parentId) => (byParent.get(parentId) || []).map(row => ({
                id: Number(row.id),
                label: row.name || "",
                items: makeItems(Number(row.id))
            }));

            return makeItems(0);
        }

        function refreshItemsGrid() {
            const source = { localdata: items, datatype: "array" };
            $("#itemsGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
            updateGridFooterTotals();
        }

        function updateGridFooterTotals() {
            const totals = getGridTotals();
            $("#purchaseItemsCount").text(totals.totalCount);
            $("#itemsGridTotalsFooter").text(
                `Total Counts: ${totals.totalCount} | Sum Total Price: ${totals.totalPriceSum.toFixed(2)}`
            );
            recalcPurchaseTotals("items");
        }

        function getPurchaseBaseAmount() {
            const totals = getGridTotals();
            const transferFee = Math.max(Number($("#transferFeeInput").jqxNumberInput("val") || 0), 0);

            return {
                subTotal: totals.totalPriceSum,
                transferFee
            };
        }

        function recalcPurchaseTotals(changedField) {
            if (totalsCalcLock) {
                return;
            }

            const { subTotal, transferFee } = getPurchaseBaseAmount();
            totalsCalcLock = true;

            let discount = Number($("#discountTotalInput").jqxNumberInput("val") || 0);
            let paid = Number($("#paidAmountInput").jqxNumberInput("val") || 0);

            if (changedField === "items") {
                paid = subTotal;
                discount = 0;
                $("#paidAmountInput").jqxNumberInput("val", paid);
                $("#discountTotalInput").jqxNumberInput("val", discount);
            } else if (changedField === "paid") {
                discount = Number((subTotal - paid).toFixed(2));
                $("#discountTotalInput").jqxNumberInput("val", discount);
            } else if (changedField === "discount") {
                paid = Number((subTotal - discount).toFixed(2));
                $("#paidAmountInput").jqxNumberInput("val", paid);
            }

            $("#subTotalDisplay").val(subTotal.toFixed(2));

            const grandTotal = Number((subTotal - discount + transferFee).toFixed(2));
            const totalPaid = Number((paid + transferFee).toFixed(2));
            $("#grandTotalDisplay").val(grandTotal.toFixed(2));
            $("#totalPaidDisplay").val(totalPaid.toFixed(2));

            totalsCalcLock = false;

            return { subTotal, transferFee, discount, paid, grandTotal, totalPaid };
        }

        function getGridTotals() {
            const rows = $("#itemsGrid").jqxGrid("getrows") || [];
            return {
                totalCount: rows.length,
                totalUnitsCount: rows.reduce((sum, r) => sum + Number(r.units_count || 0), 0),
                totalPriceSum: rows.reduce((sum, r) => sum + Number(r.total_price || 0), 0)
            };
        }

        function submitPurchase(payload) {
            $.ajax({
                url: API_URLS.purchases,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function(res) {
                $("#messageBox").text(res.message || "Purchase saved.");
                setTimeout(function () {
                    window.location.href = "<?= site_url('purchases') ?>";
                }, 500);
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.error
                    ? `${xhr.responseJSON.message}: ${xhr.responseJSON.error}`
                    : (xhr.responseJSON?.message || "Failed to save purchase.");
                $("#messageBox").text(msg);
            });
        }

        function loadSuppliers() {
            return $.getJSON(API_URLS.suppliers).done(function(res) {
                suppliers = res.data || [];
                $("#supplierDropdown").jqxDropDownList({ source: suppliers });
                if (suppliers.length === 0) {
                    $("#messageBox").text("No suppliers found. Please add supplier data first.");
                }
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load suppliers.";
                $("#messageBox").text(msg);
            });
        }

        function loadCategories() {
            return $.getJSON(API_URLS.categories).done(function(res) {
                categories = res.data || [];
                const treeItems = buildCategoryTreeItems(categories);
                $("#categoryTree").jqxTree({
                    source: treeItems,
                    width: 270,
                    height: 260
                });
                $("#categoryTree").jqxTree("expandAll");
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load categories.";
                $("#messageBox").text(msg);
            });
        }

        function loadWarehouses() {
            return $.getJSON(API_URLS.warehouses).done(function(res) {
                warehouses = res.data || [];
                $("#purchaseWarehouseDropdown").jqxDropDownList({ source: warehouses });
                if (warehouses.length > 0) {
                    $("#purchaseWarehouseDropdown").jqxDropDownList("selectIndex", 0);
                }
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load warehouses.";
                $("#messageBox").text(msg);
            });
        }

        function clearProductForm() {
            $("#productNameInput").val("");
            $("#productSerialInput").val("");
            $("#productBrandInput").val("");
            $("#productStyleInput").val("");
            $("#unitPriceInput").val(0);
            recalcTotalUnits();
        }

        function recalcTotalUnits() {
            const setsCount = Number($("#setsCountInput").jqxNumberInput("val") || 0);
            const totalUnits = Math.max(setsCount, 0) * Math.max(selectedSizeCount, 0);
            $("#totalUnitsInput").jqxNumberInput("val", totalUnits);
            recalcTotalPrice();
        }

        function recalcTotalPrice() {
            const totalUnits = Number($("#totalUnitsInput").jqxNumberInput("val") || 0);
            const usdUnitPrice = getUsdUnitPrice();
            const totalPrice = Number((Math.max(usdUnitPrice, 0) * Math.max(totalUnits, 0)).toFixed(2));
            $("#totalPriceInput").jqxNumberInput("val", totalPrice);
            updateUnitPriceCurrencyUi();
        }

        function addProductToGrid() {
            const name = String($("#productNameInput").val() || "").trim();
            const serialNumber = String($("#productSerialInput").val() || "").trim();
            const brand = String($("#productBrandInput").val() || "").trim();
            const styleValue = String($("#productStyleInput").val() || "").trim();
            const checkedSizes = $("#sizeSelector").jqxDropDownList("getCheckedItems") || [];
            const setsCount = Number($("#setsCountInput").jqxNumberInput("val") || 0);
            const unitsCount = Number($("#totalUnitsInput").jqxNumberInput("val") || 0);
            const selectedCurrency = getSelectedUnitCurrency();
            if (selectedCurrency !== "USD" && getExchangeRateForCurrency(selectedCurrency) <= 0) {
                $("#messageBox").text("Please set exchange rate.");
                openExchangeRateModal();
                return;
            }
            const unitCost = getUsdUnitPrice();
            const totalPrice = Number((unitCost * unitsCount).toFixed(2));
            const selectedWarehouse = $("#purchaseWarehouseDropdown").jqxDropDownList("getSelectedItem");
            const warehouseId = Number(selectedWarehouse?.value || 0);

            if (!name) {
                $("#messageBox").text("Product name is required.");
                return;
            }
            if (!serialNumber) {
                $("#messageBox").text("Serial number is required.");
                return;
            }
            if (!selectedCategoryId) {
                $("#messageBox").text("Please select category from category tree.");
                return;
            }
            if (checkedSizes.length === 0) {
                $("#messageBox").text("Please select at least one size.");
                return;
            }
            if (!warehouseId) {
                $("#messageBox").text("Please select warehouse.");
                return;
            }
            if (setsCount <= 0) {
                $("#messageBox").text("Sets count must be greater than 0.");
                return;
            }

            $("#addProductsBtn").prop("disabled", true);

            $.ajax({
                url: API_URLS.products,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    name,
                    serial_number: serialNumber,
                    category_id: Number(selectedCategoryId),
                    brand: brand || null,
                    tags: getSelectedTagIds()
                })
            }).done(function (res) {
                const createdId = Number(res.data?.id || 0);
                if (createdId < 1) {
                    $("#messageBox").text("Failed to register product.");
                    return;
                }

                const rowData = {
                    product_id: createdId,
                    product_variant_id: 0,
                    product_name: name,
                    sku: serialNumber,
                    brand: brand,
                    style: styleValue,
                    warehouse_id: warehouseId,
                    warehouse_name: String(selectedWarehouse?.label || ""),
                    unit_cost: Number(unitCost.toFixed(2)),
                    size_value: checkedSizes.map(s => String(s.value)).join(", "),
                    sets_count: setsCount,
                    units_count: unitsCount,
                    total_price: Number(totalPrice.toFixed(2))
                };
                $("#itemsGrid").jqxGrid("addrow", null, rowData);
                updateGridFooterTotals();
                clearProductForm();
                $("#messageBox").text("Product registered and added to grid.");
            }).fail(function (xhr) {
                $("#messageBox").text(xhr.responseJSON?.message || "Failed to register product.");
            }).always(function () {
                $("#addProductsBtn").prop("disabled", false);
            });
        }

        function removeSelectedRow() {
            const index = $("#itemsGrid").jqxGrid("getselectedrowindex");
            if (index === -1) {
                $("#messageBox").text("Select an item row to remove.");
                return;
            }
            const rowId = $("#itemsGrid").jqxGrid("getrowid", index);
            $("#itemsGrid").jqxGrid("deleterow", rowId);
            updateGridFooterTotals();
        }

        function getValidItems() {
            const rows = $("#itemsGrid").jqxGrid("getrows") || [];
            return rows
                .filter(r => Number(r.product_id) > 0 && Number(r.sets_count) > 0)
                .map(r => ({
                    product_id: Number(r.product_id || 0),
                    sizes: String(r.size_value || "")
                        .split(",")
                        .map(v => v.trim())
                        .filter(v => v !== ""),
                    sets_count: Number(r.sets_count || 0),
                    qty: Number(r.units_count),
                    warehouse_id: Number(r.warehouse_id || 0),
                    unit_cost: Number(r.unit_cost || 0),
                    discount_amount: 0,
                    style: String(r.style || "")
                }));
        }

        function savePurchase() {
            const selectedSupplier = $("#supplierDropdown").jqxDropDownList("getSelectedItem");
            const supplierId = selectedSupplier ? Number(selectedSupplier.value) : 0;
            if (!supplierId) {
                $("#messageBox").text("Please select a supplier.");
                return;
            }

            const validItems = getValidItems();
            if (validItems.length === 0) {
                $("#messageBox").text("Supplier and at least 1 item are required.");
                return;
            }

            const purchaseDate = $("#purchaseDate").jqxDateTimeInput("getText");
            if (!purchaseDate) {
                $("#messageBox").text("Purchase date is required.");
                return;
            }

            const { subTotal, transferFee } = getPurchaseBaseAmount();
            const totalsSummary = recalcPurchaseTotals("discount");
            const payload = {
                supplier_id: supplierId,
                purchase_date: purchaseDate,
                notes: $("#notesInput").val(),
                transfer_fee: transferFee,
                discount_total: totalsSummary.discount,
                paid_total: totalsSummary.paid,
                payment_method: String($("#paymentMethodSelect").val() || "cash"),
                calculation_currency: getSelectedUnitCurrency(),
                // tag_ids: getSelectedTagIds(),
                items: validItems
            };
            const totals = getGridTotals();
            $("#confirmTotalUnitsCount").text(totals.totalUnitsCount);
            $("#confirmTotalPriceSum").text(subTotal.toFixed(2));
            $("#confirmTransferFee").text(transferFee.toFixed(2));
            $("#confirmDiscount").text(totalsSummary.discount.toFixed(2));
            $("#confirmGrandTotal").text(totalsSummary.grandTotal.toFixed(2));
            $("#confirmPaidAmount").text(totalsSummary.paid.toFixed(2));
            $("#confirmSavePurchaseBtn").off("click").on("click", function () {
                if (confirmPurchaseModal) {
                    confirmPurchaseModal.hide();
                }
                submitPurchase(payload);
            });
            if (confirmPurchaseModal) {
                confirmPurchaseModal.show();
            }
        }

        $(function() {
            initWidgets();
            loadSuppliers();
            loadCategories();
            loadWarehouses();
            loadTags();
            loadCurrencies();
            confirmPurchaseModal = new bootstrap.Modal(document.getElementById("purchaseConfirmModal"));
            exchangeRateModal = new bootstrap.Modal(document.getElementById("exchangeRateModal"));

            $("#unitPriceCurrency").on("change", function () {
                loadExchangeRateForCurrency(getSelectedUnitCurrency());
            });
            $("#unitPriceRateLink").on("click keydown", function (event) {
                if (event.type === "keydown" && event.key !== "Enter" && event.key !== " ") {
                    return;
                }
                event.preventDefault();
                openExchangeRateModal();
            });
            $("#saveExchangeRateBtn").on("click", saveExchangeRateFromModal);
            $("#exchangeRateInput").on("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    saveExchangeRateFromModal();
                }
            });

            $("#categoryTree").on("select", function (event) {
                const args = event.args;
                const item = $("#categoryTree").jqxTree("getItem", args.element);
                if (!item) {
                    return;
                }

                selectedCategoryId = Number(item.id || 0);
                const dropDownContent = `<div style="position: relative; margin-left: 3px; margin-top: 5px;">${item.label}</div>`;
                $("#categoryDropDownButton").jqxDropDownButton("setContent", dropDownContent);
                $("#categoryDropDownButton").jqxDropDownButton("close");
            });

            $("#sizeSelector").on("checkChange", function () {
                const checkedItems = $("#sizeSelector").jqxDropDownList("getCheckedItems") || [];
                selectedSizeCount = checkedItems.length;
                recalcTotalUnits();
            });

            $("#setsCountInput").on("valueChanged", recalcTotalUnits);
            $("#unitPriceInput").on("input change", function () {
                updateUnitPriceCurrencyUi();
                recalcTotalPrice();
            });
            $("#transferFeeInput").on("valueChanged", function () {
                recalcPurchaseTotals("transfer");
            });
            $("#discountTotalInput").on("valueChanged", function () {
                recalcPurchaseTotals("discount");
            });
            $("#paidAmountInput").on("valueChanged", function () {
                recalcPurchaseTotals("paid");
            });

            $("#addProductsBtn").on("click", addProductToGrid);
            $("#createTagBtn").on("click", createTagFromInput);
            $("#newTagNameInput").on("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    createTagFromInput();
                }
            });
            recalcPurchaseTotals("items");
            $("#savePurchaseBtn").on("click", savePurchase);
        });
</script>
<?= $this->endSection() ?>
