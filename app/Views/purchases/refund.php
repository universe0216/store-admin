<?php

use App\Enums\Department;
use App\Enums\Gender;
use App\Enums\Season;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Refund Purchase<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    #refundHeaderForm .jqx-numberinput input,
    #refundSummaryForm .jqx-numberinput input {
        height: 100% !important;
        line-height: 34px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    #stockGrid .jqx-grid-cell-hover {
        cursor: pointer;
    }
    .refund-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.4rem 0;
    }
    .refund-summary-row .summary-label {
        color: #6c757d;
    }
    .refund-summary-row .summary-value {
        min-width: 140px;
        text-align: right;
    }
    #refundSummaryForm .jqx-numberinput {
        width: 140px !important;
    }
    #refundSummaryForm hr {
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
                    <h1 class="h4 fw-bold mb-0">Refund Purchase</h1>
                </div>
                <div id="refundHeaderForm" class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Supplier</label>
                        <div id="supplierDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Refund Warehouse</label>
                        <div id="refundWarehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Refund Date</label>
                        <div id="refundDateInput"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Notes</label>
                        <input type="text" id="refundNotesInput" class="form-control" placeholder="Optional">
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Warehouse Stock</h2>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-5">
                                <div id="stockWarehouseDropdown"></div>
                            </div>
                            <div class="col-12 col-md-7">
                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <select id="stockDepartmentSelect" class="form-select form-select-sm">
                                            <option value="">All departments</option>
                                            <?php foreach (Department::cases() as $case) : ?>
                                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <select id="stockGenderSelect" class="form-select form-select-sm">
                                            <option value="">All genders</option>
                                            <?php foreach (Gender::cases() as $case) : ?>
                                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <select id="stockSeasonSelect" class="form-select form-select-sm">
                                            <option value="">All seasons</option>
                                            <?php foreach (Season::cases() as $case) : ?>
                                                <option value="<?= esc($case->value, 'attr') ?>"><?= esc($case->label()) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex gap-2">
                                            <input type="text" id="stockSearchInput" class="form-control" placeholder="Search by product name or product number">
                                            <button type="button" id="clearStockFiltersBtn" class="btn btn-outline-secondary btn-sm text-nowrap">Clear</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="stockGrid"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Refund Items (<span id="refundItemsCount">0</span>)</h2>
                        <div id="refundItemsGrid"></div>
                    </div>
                </div>

                <div id="refundSummaryForm" class="card shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Refund Summary</h2>
                        <div class="refund-summary-row">
                            <span class="summary-label">Subtotal</span>
                            <div class="summary-value">
                                <input id="subTotalDisplay" type="text" class="form-control form-control-sm bg-light text-end" readonly value="0.00">
                            </div>
                        </div>
                        <hr>
                        <div class="refund-summary-row">
                            <span class="summary-label fw-semibold text-dark">Refund Total</span>
                            <div class="summary-value">
                                <input id="refundTotalDisplay" type="text" class="form-control form-control-sm bg-light text-end fw-semibold" readonly value="0.00">
                            </div>
                        </div>
                        <div class="refund-summary-row">
                            <span class="summary-label">Refund Received</span>
                            <div class="summary-value">
                                <div id="refundReceivedInput"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= site_url('purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="button" id="saveRefundBtn" class="btn btn-warning">Save Refund</button>
        </div>
    </div>

    <div class="modal fade" id="refundConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Refund Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">Total Items Qty: <span id="confirmTotalItemsQty" class="fw-semibold">0</span></div>
                    <div class="mb-2">Subtotal: <span id="confirmSubTotal" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Refund Total: <span id="confirmRefundTotal" class="fw-semibold">0.00</span></div>
                    <div class="mb-2">Refund Received: <span id="confirmRefundReceived" class="fw-semibold">0.00</span></div>
                    <hr>
                    <div class="mb-2 fw-semibold">Refund Payment Methods</div>
                    <div id="confirmPaymentRows" class="d-flex flex-column gap-2 mb-2"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentRowBtn">+ Add Payment Method</button>
                    <div class="small text-muted mt-2">Payment amounts must equal the refund total.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmSaveRefundBtn">Confirm Save</button>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        suppliers: "<?= site_url('api/suppliers') ?>",
        warehouses: "<?= site_url('api/warehouses') ?>",
        warehouseProducts: "<?= site_url('api/warehouse-products') ?>",
        refundPurchases: "<?= site_url('api/purchases/refund') ?>",
        paymentMethods: "<?= site_url('api/payment-methods') ?>"
    };

    let stockSource = [];
    let refundItems = [];
    let paymentMethods = [];
    let confirmPaymentRows = [];
    let confirmRefundModal = null;
    let stockSearchTerm = "";
    let stockDepartmentFilter = "";
    let stockGenderFilter = "";
    let stockSeasonFilter = "";

    function itemKey(inventoryId) {
        return String(inventoryId);
    }

    function initWidgets() {
        $("#refundDateInput").jqxDateTimeInput({ width: "100%", height: 34, formatString: "yyyy-MM-dd" });
        $("#supplierDropdown").jqxDropDownList({
            width: "100%",
            height: 34,
            displayMember: "name",
            valueMember: "id",
            placeHolder: "Select supplier"
        });
        $("#refundWarehouseDropdown").jqxDropDownList({
            width: "100%",
            height: 34,
            displayMember: "name",
            valueMember: "id",
            placeHolder: "Select warehouse"
        });
        $("#stockWarehouseDropdown").jqxDropDownList({
            width: "100%",
            height: 34,
            displayMember: "name",
            valueMember: "id",
            placeHolder: "All warehouses"
        });
        $("#refundReceivedInput").jqxNumberInput({
            width: "100%",
            height: 34,
            decimalDigits: 2,
            digits: 12,
            min: 0,
            inputMode: "simple",
            spinButtons: true,
            value: 0
        });

        $("#stockGrid").jqxGrid({
            width: "100%",
            height: 520,
            source: new $.jqx.dataAdapter({ localdata: stockSource, datatype: "array" }),
            selectionmode: "singlerow",
            columnsresize: true,
            enablehover: true,
            columns: [
                { text: "Warehouse", datafield: "warehouse_name", width: 100 },
                { text: "Product", datafield: "product_name", width: 130 },
                { text: "Product No.", datafield: "product_number", width: 100 },
                { text: "Style", datafield: "style", width: 90 },
                { text: "Size", datafield: "size_value", width: 70 },
                { text: "Brand", datafield: "brand", width: 90 },
                { text: "Available", datafield: "remaining_qty", width: 90, cellsalign: "right" },
                { text: "Cost Price", datafield: "cost_price", width: 100, cellsformat: "f2", cellsalign: "right" }
            ]
        });

        $("#refundItemsGrid").jqxGrid({
            width: "100%",
            height: 520,
            source: new $.jqx.dataAdapter({ localdata: refundItems, datatype: "array" }),
            editable: true,
            editmode: "click",
            selectionmode: "singlerow",
            columnsresize: true,
            showtoolbar: true,
            showstatusbar: true,
            statusbarheight: 34,
            rendertoolbar: function (toolbar) {
                const container = $('<div class="d-flex align-items-center h-100 px-2"></div>');
                const deleteBtn = $('<button type="button" id="removeRefundItemBtn" class="btn btn-sm btn-outline-danger">Remove Selected</button>');
                container.append(deleteBtn);
                toolbar.append(container);
                container.on("click", "#removeRefundItemBtn", removeSelectedRefundItem);
            },
            renderstatusbar: function (statusbar) {
                const footer = $('<div id="refundItemsTotalsFooter" class="d-flex align-items-center h-100 px-2 small fw-semibold text-secondary"></div>');
                statusbar.append(footer);
                updateRefundTotalsFooter();
            },
            columns: [
                { text: "Product", datafield: "product_name", width: 160, editable: false },
                { text: "SKU", datafield: "sku", width: 110, editable: false },
                { text: "Size", datafield: "size_value", width: 70, editable: false },
                { text: "Warehouse", datafield: "warehouse_name", width: 100, editable: false },
                { text: "Qty", datafield: "qty", width: 70, cellsalign: "right", columntype: "numberinput" },
                { text: "Unit Cost", datafield: "unit_cost", width: 110, cellsformat: "f2", cellsalign: "right", columntype: "numberinput" },
                { text: "Line Total", datafield: "line_total", width: 110, cellsformat: "f2", cellsalign: "right", editable: false }
            ]
        });

        $("#stockGrid").on("rowclick", function (event) {
            const row = $("#stockGrid").jqxGrid("getrowdata", event.args.rowindex);
            if (row) {
                addOneToRefund(row);
            }
        });

        $("#refundItemsGrid").on("cellvaluechanged", function (event) {
            syncRefundItemFromGrid(event.args.rowindex, event.args.datafield);
        });
    }

    function getFilteredStockRows() {
        const term = stockSearchTerm.trim().toLowerCase();

        return stockSource.filter(r => {
            if (Number(r.remaining_qty) <= 0) {
                return false;
            }
            if (stockDepartmentFilter !== "" && String(r.department || "") !== stockDepartmentFilter) {
                return false;
            }
            if (stockGenderFilter !== "" && String(r.gender || "") !== stockGenderFilter) {
                return false;
            }
            if (stockSeasonFilter !== "" && String(r.season || "") !== stockSeasonFilter) {
                return false;
            }
            if (term === "") {
                return true;
            }

            const name = String(r.product_name || "").toLowerCase();
            const productNumber = String(r.product_number || "").toLowerCase();

            return name.includes(term) || productNumber.includes(term);
        });
    }

    function refreshStockGrid() {
        $("#stockGrid").jqxGrid({ source: new $.jqx.dataAdapter({ localdata: getFilteredStockRows(), datatype: "array" }) });
    }

    function clearStockFilters() {
        stockSearchTerm = "";
        stockDepartmentFilter = "";
        stockGenderFilter = "";
        stockSeasonFilter = "";
        $("#stockSearchInput").val("");
        $("#stockDepartmentSelect").val("");
        $("#stockGenderSelect").val("");
        $("#stockSeasonSelect").val("");
        $("#stockWarehouseDropdown").jqxDropDownList("clearSelection");
        loadStock();
    }

    function refreshRefundGrid() {
        $("#refundItemsGrid").jqxGrid({ source: new $.jqx.dataAdapter({ localdata: refundItems, datatype: "array" }) });
        updateRefundTotalsFooter();
    }

    function getRefundSubTotal() {
        return (refundItems || []).reduce(
            (sum, r) => sum + Number(r.qty || 0) * Number(r.unit_cost || 0),
            0
        );
    }

    function recalcRefundTotals(changedField) {
        const subTotal = getRefundSubTotal();
        $("#subTotalDisplay").val(subTotal.toFixed(2));
        $("#refundTotalDisplay").val(subTotal.toFixed(2));

        if (changedField === "items") {
            $("#refundReceivedInput").jqxNumberInput("val", subTotal);
        }

        return { subTotal, refundTotal: subTotal };
    }

    function updateRefundTotalsFooter() {
        const rows = refundItems || [];
        $("#refundItemsCount").text(rows.length);
        const totalQty = rows.reduce((sum, r) => sum + Number(r.qty || 0), 0);
        const totals = recalcRefundTotals("redistribute");
        $("#refundItemsTotalsFooter").text(
            `Total Qty: ${totalQty} | Refund Total: ${totals.refundTotal.toFixed(2)}`
        );
    }

    function findStockByInventoryId(inventoryId) {
        return stockSource.find(s => Number(s.inventory_id) === Number(inventoryId));
    }

    function findRefundItem(inventoryId) {
        return refundItems.find(s => Number(s.inventory_id) === Number(inventoryId));
    }

    function addOneToRefund(stockRow) {
        const inventoryId = Number(stockRow.inventory_id);
        const stock = findStockByInventoryId(inventoryId);
        if (!stock || Number(stock.remaining_qty) < 1) {
            setMessage("No stock available for this product.", true);
            return;
        }

        stock.remaining_qty = Number(stock.remaining_qty) - 1;

        let refundRow = findRefundItem(inventoryId);
        const unitCost = Number(stock.cost_price || 0);
        if (refundRow) {
            refundRow.qty = Number(refundRow.qty) + 1;
            refundRow.line_total = Number((refundRow.qty * refundRow.unit_cost).toFixed(2));
        } else {
            refundItems.push({
                inventory_id: inventoryId,
                product_variant_id: Number(stock.variant_id),
                warehouse_id: Number(stock.warehouse_id || 0),
                warehouse_name: stock.warehouse_name || "",
                product_name: stock.product_name || "",
                sku: stock.sku || "",
                size_value: stock.size_value || "",
                unit_cost: unitCost,
                qty: 1,
                line_total: unitCost
            });
        }

        refreshStockGrid();
        refreshRefundGrid();
        recalcRefundTotals("items");
        setMessage("Item added to refund list.", false);
    }

    function syncRefundItemFromGrid(rowIndex, datafield) {
        const gridRow = $("#refundItemsGrid").jqxGrid("getrowdata", rowIndex);
        if (!gridRow) {
            return;
        }

        const inventoryId = Number(gridRow.inventory_id);
        const refundRow = findRefundItem(inventoryId);
        if (!refundRow) {
            return;
        }

        const field = String(datafield || "");

        if (field === "qty") {
            const oldQty = Number(refundRow.qty);
            const newQty = Math.max(1, Number(gridRow.qty || 1));
            const stock = findStockByInventoryId(inventoryId);
            const maxQty = oldQty + Number(stock?.remaining_qty || 0);
            const adjustedQty = Math.min(newQty, maxQty);

            if (adjustedQty !== newQty) {
                setMessage("Qty adjusted to available stock.", true);
            }

            const qtyDelta = adjustedQty - oldQty;
            if (stock) {
                stock.remaining_qty = Number(stock.remaining_qty) - qtyDelta;
            }
            refundRow.qty = adjustedQty;
        } else if (field === "unit_cost") {
            refundRow.unit_cost = Number(Number(gridRow.unit_cost || 0).toFixed(2));
        }

        refundRow.line_total = Number((Number(refundRow.qty) * Number(refundRow.unit_cost)).toFixed(2));

        refreshStockGrid();
        refreshRefundGrid();
        recalcRefundTotals("items");
    }

    function removeSelectedRefundItem() {
        const index = $("#refundItemsGrid").jqxGrid("getselectedrowindex");
        if (index < 0) {
            setMessage("Select a refund item row to remove.", true);
            return;
        }

        const row = $("#refundItemsGrid").jqxGrid("getrowdata", index);
        if (!row) {
            return;
        }

        const stock = findStockByInventoryId(row.inventory_id);
        if (stock) {
            stock.remaining_qty = Number(stock.remaining_qty) + Number(row.qty || 0);
        }

        refundItems = refundItems.filter(s => Number(s.inventory_id) !== Number(row.inventory_id));
        refreshStockGrid();
        refreshRefundGrid();
        recalcRefundTotals("items");
        setMessage("Item removed from refund list.", false);
    }

    function loadSuppliers() {
        return $.getJSON(API_URLS.suppliers).done(function(res) {
            $("#supplierDropdown").jqxDropDownList({ source: res.data || [] });
            $("#supplierDropdown").jqxDropDownList("clearSelection");
        });
    }

    function loadWarehouses() {
        return $.getJSON(API_URLS.warehouses).done(function(res) {
            const rows = res.data || [];
            $("#refundWarehouseDropdown").jqxDropDownList({ source: rows });
            $("#stockWarehouseDropdown").jqxDropDownList({ source: rows });
            $("#refundWarehouseDropdown").jqxDropDownList("clearSelection");
            $("#stockWarehouseDropdown").jqxDropDownList("clearSelection");
        });
    }

    function getStockWarehouseFilterId() {
        const selected = $("#stockWarehouseDropdown").jqxDropDownList("getSelectedItem");
        return selected ? Number(selected.value) : 0;
    }

    function loadStock() {
        const warehouseId = getStockWarehouseFilterId();
        const params = warehouseId > 0 ? { warehouse_id: warehouseId } : {};

        $.getJSON(API_URLS.warehouseProducts, params).done(function(res) {
            stockSource = (res.data || []).map(p => {
                const qty = Number(p.quantity || 0);
                return {
                    inventory_id: Number(p.inventory_id || 0),
                    variant_id: Number(p.variant_id),
                    warehouse_id: Number(p.warehouse_id || 0),
                    warehouse_name: p.warehouse_name || "",
                    product_name: p.product_name || "",
                    product_number: p.product_number || "",
                    sku: p.sku || "",
                    style: p.style || "",
                    size_value: p.size_value || "",
                    brand: p.brand || "",
                    department: p.department || "",
                    gender: p.gender || "",
                    season: p.season || "",
                    cost_price: Number(p.cost_price || 0),
                    original_qty: qty,
                    remaining_qty: qty
                };
            });
            for (const item of refundItems) {
                const stock = findStockByInventoryId(item.inventory_id);
                if (stock) {
                    stock.remaining_qty = Number(stock.remaining_qty) - Number(item.qty || 0);
                }
            }
            refreshStockGrid();
            if (!stockSource.length) {
                setMessage("No stock found.", true);
            }
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to load stock.", true);
        });
    }

    function escapeHtml(text) {
        return String(text || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function loadPaymentMethods() {
        return $.getJSON(API_URLS.paymentMethods).done(function (res) {
            paymentMethods = (res.data || []).filter(row => Number(row.is_active ?? 1) === 1);
        });
    }

    function buildPaymentMethodOptions(selectedCode) {
        const options = paymentMethods.map(row => {
            const code = String(row.code || "");
            const selected = code === selectedCode ? " selected" : "";
            return `<option value="${escapeHtml(code)}"${selected}>${escapeHtml(row.name || code)}</option>`;
        }).join("");

        if (options === "") {
            return `
                <option value="cash"${selectedCode === "cash" ? " selected" : ""}>Cash</option>
                <option value="bank_transfer"${selectedCode === "bank_transfer" ? " selected" : ""}>Bank Transfer</option>
            `;
        }

        return options;
    }

    function getConfirmPaymentRowsData() {
        const rows = [];
        $("#confirmPaymentRows .confirm-payment-row").each(function () {
            const method = String($(this).find(".confirm-payment-method").val() || "").trim();
            const amount = Number($(this).find(".confirm-payment-amount").val() || 0);
            if (method !== "" && amount > 0) {
                rows.push({ payment_method: method, amount: Number(amount.toFixed(2)) });
            }
        });
        return rows;
    }

    function updateConfirmPaymentSummary() {
        const refundTotal = Number($("#confirmRefundTotal").text() || 0);
        const payments = getConfirmPaymentRowsData();
        const received = payments.reduce((sum, row) => sum + row.amount, 0);
        $("#confirmRefundReceived").text(received.toFixed(2));
        if (Math.abs(received - refundTotal) > 0.01) {
            $("#confirmRefundReceived").addClass("text-danger");
        } else {
            $("#confirmRefundReceived").removeClass("text-danger");
        }
    }

    function renderConfirmPaymentRows(refundTotal) {
        const $container = $("#confirmPaymentRows").empty();
        const rows = confirmPaymentRows.length > 0
            ? confirmPaymentRows
            : [{ payment_method: "cash", amount: refundTotal }];

        rows.forEach(function (row, index) {
            const rowEl = $(`
                <div class="confirm-payment-row d-flex gap-2 align-items-center">
                    <select class="form-select form-select-sm confirm-payment-method" style="max-width: 180px;">
                        ${buildPaymentMethodOptions(String(row.payment_method || "cash"))}
                    </select>
                    <input type="number" class="form-control form-control-sm confirm-payment-amount text-end" min="0" step="0.01" value="${Number(row.amount || 0).toFixed(2)}">
                    <button type="button" class="btn btn-sm btn-outline-danger confirm-remove-payment-row"${index === 0 ? " disabled" : ""}>Remove</button>
                </div>
            `);
            $container.append(rowEl);
        });

        $container.off("input change", ".confirm-payment-amount, .confirm-payment-method").on("input change", ".confirm-payment-amount, .confirm-payment-method", updateConfirmPaymentSummary);
        $container.off("click", ".confirm-remove-payment-row").on("click", ".confirm-remove-payment-row", function () {
            $(this).closest(".confirm-payment-row").remove();
            updateConfirmPaymentSummary();
        });

        updateConfirmPaymentSummary();
    }

    function submitRefund(payload) {
        $("#saveRefundBtn").prop("disabled", true);
        $("#confirmSaveRefundBtn").prop("disabled", true);
        $.ajax({
            url: API_URLS.refundPurchases,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload)
        }).done(function(res) {
            setMessage(res.message || "Refund saved.", false);
            setTimeout(function () {
                window.location.href = "<?= site_url('purchases') ?>";
            }, 500);
        }).fail(function(xhr) {
            const msg = xhr.responseJSON?.error
                ? `${xhr.responseJSON.message}: ${xhr.responseJSON.error}`
                : (xhr.responseJSON?.message || "Failed to save refund.");
            setMessage(msg, true);
        }).always(function () {
            $("#saveRefundBtn").prop("disabled", false);
            $("#confirmSaveRefundBtn").prop("disabled", false);
        });
    }

    function saveRefund() {
        const supplierItem = $("#supplierDropdown").jqxDropDownList("getSelectedItem");
        const supplierId = supplierItem ? Number(supplierItem.value) : 0;
        if (!supplierId) {
            setMessage("Please select a supplier.", true);
            return;
        }

        const warehouseItem = $("#refundWarehouseDropdown").jqxDropDownList("getSelectedItem");
        const warehouseId = warehouseItem ? Number(warehouseItem.value) : 0;
        if (!warehouseId) {
            setMessage("Please select refund warehouse.", true);
            return;
        }

        if (refundItems.length === 0) {
            setMessage("Add at least one item.", true);
            return;
        }

        const zeroCostItems = refundItems.filter(r => Number(r.unit_cost || 0) <= 0);
        if (zeroCostItems.length > 0) {
            setMessage("All items must have a unit cost greater than 0.", true);
            return;
        }

        const refundDate = $("#refundDateInput").jqxDateTimeInput("getText");
        if (!refundDate) {
            setMessage("Refund date is required.", true);
            return;
        }

        const totals = recalcRefundTotals("redistribute");
        const totalQty = refundItems.reduce((sum, r) => sum + Number(r.qty || 0), 0);
        const refundReceived = Number($("#refundReceivedInput").jqxNumberInput("val") || 0);

        if (Math.abs(refundReceived - totals.refundTotal) > 0.01) {
            setMessage("Refund received must equal the refund total.", true);
            return;
        }

        const payload = {
            supplier_id: supplierId,
            warehouse_id: warehouseId,
            purchase_date: refundDate,
            notes: String($("#refundNotesInput").val() || "").trim(),
            payment_method: "cash",
            items: refundItems.map(r => ({
                product_variant_id: Number(r.product_variant_id),
                warehouse_id: Number(r.warehouse_id),
                qty: Number(r.qty),
                unit_cost: Number(r.unit_cost)
            }))
        };

        $("#confirmTotalItemsQty").text(totalQty);
        $("#confirmSubTotal").text(totals.subTotal.toFixed(2));
        $("#confirmRefundTotal").text(totals.refundTotal.toFixed(2));
        $("#confirmRefundReceived").text(refundReceived.toFixed(2));

        confirmPaymentRows = [{ payment_method: "cash", amount: totals.refundTotal }];
        renderConfirmPaymentRows(totals.refundTotal);

        $("#confirmSaveRefundBtn").off("click").on("click", function () {
            const confirmRefundTotal = Number($("#confirmRefundTotal").text() || 0);
            const payments = getConfirmPaymentRowsData();
            const paymentSum = payments.reduce((sum, row) => sum + row.amount, 0);
            if (Math.abs(paymentSum - confirmRefundTotal) > 0.01) {
                setMessage("Payment amounts must equal the refund total.", true);
                return;
            }

            payload.payments = payments;
            payload.payment_method = payments[0]?.payment_method || "cash";

            if (confirmRefundModal) {
                confirmRefundModal.hide();
            }
            submitRefund(payload);
        });

        if (confirmRefundModal) {
            confirmRefundModal.show();
        }
    }

    $(function() {
        initWidgets();
        loadPaymentMethods();
        confirmRefundModal = new bootstrap.Modal(document.getElementById("refundConfirmModal"));
        loadSuppliers();
        loadWarehouses().done(loadStock);

        $("#stockWarehouseDropdown").on("select", loadStock);
        $("#saveRefundBtn").on("click", saveRefund);
        $("#addPaymentRowBtn").on("click", function () {
            const payments = getConfirmPaymentRowsData();
            const refundTotal = Number($("#confirmRefundTotal").text() || 0);
            const allocated = payments.reduce((sum, row) => sum + row.amount, 0);
            const remaining = Number(Math.max(0, refundTotal - allocated).toFixed(2));
            confirmPaymentRows = payments;
            confirmPaymentRows.push({
                payment_method: paymentMethods[1]?.code || "bank_transfer",
                amount: remaining
            });
            renderConfirmPaymentRows(refundTotal);
        });
        $("#stockSearchInput").on("input", function () {
            stockSearchTerm = String($(this).val() || "");
            refreshStockGrid();
        });
        $("#clearStockFiltersBtn").on("click", clearStockFilters);
        $("#stockDepartmentSelect, #stockGenderSelect, #stockSeasonSelect").on("change", function () {
            stockDepartmentFilter = String($("#stockDepartmentSelect").val() || "");
            stockGenderFilter = String($("#stockGenderSelect").val() || "");
            stockSeasonFilter = String($("#stockSeasonSelect").val() || "");
            refreshStockGrid();
        });
        $("#refundReceivedInput").on("valueChanged", function () {
            recalcRefundTotals("redistribute");
        });
        recalcRefundTotals("items");
    });
</script>
<?= $this->endSection() ?>
