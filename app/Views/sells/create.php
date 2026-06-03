<?php

use App\Enums\Department;
use App\Enums\Gender;
use App\Enums\Season;

?>
<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>New Sale<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    #saleHeaderForm .jqx-numberinput input,
    #saleSummaryForm .jqx-numberinput input,
    #discountTotalInput input,
    #paidAmountInput input {
        height: 100% !important;
        line-height: 34px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    #stockGrid .jqx-grid-cell-hover {
        cursor: pointer;
    }
    .sale-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.4rem 0;
    }
    .sale-summary-row .summary-label {
        color: #6c757d;
    }
    .sale-summary-row .summary-value {
        min-width: 140px;
        text-align: right;
    }
    .sale-summary-row .summary-value input.form-control,
    .sale-summary-row .summary-value .jqx-numberinput {
        text-align: right;
    }
    #saleSummaryForm .jqx-numberinput {
        width: 140px !important;
    }
    #saleSummaryForm hr {
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
                    <h1 class="h4 fw-bold mb-0">New Sale</h1>
                </div>
                <div id="saleHeaderForm" class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Sale Warehouse</label>
                        <div id="saleWarehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Sale Date</label>
                        <div id="saleDateInput"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Customer Name</label>
                        <input type="text" id="customerNameInput" class="form-control" placeholder="Optional">
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
                        <h2 class="h6 fw-semibold mb-3">Sale Items (<span id="saleItemsCount">0</span>)</h2>
                        <div id="saleItemsGrid"></div>
                    </div>
                </div>

                <div id="saleSummaryForm" class="card shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Sale Summary</h2>
                        <div class="sale-summary-row">
                            <span class="summary-label">Subtotal</span>
                            <div class="summary-value">
                                <input id="subTotalDisplay" type="text" class="form-control form-control-sm bg-light text-end" readonly value="0.00">
                            </div>
                        </div>
                        <div class="sale-summary-row">
                            <span class="summary-label">Discount</span>
                            <div class="summary-value">
                                <div id="discountTotalInput"></div>
                            </div>
                        </div>
                        <hr>
                        <div class="sale-summary-row">
                            <span class="summary-label fw-semibold text-dark">Grand Total</span>
                            <div class="summary-value">
                                <input id="grandTotalDisplay" type="text" class="form-control form-control-sm bg-light text-end fw-semibold" readonly value="0.00">
                            </div>
                        </div>
                        <div class="sale-summary-row">
                            <span class="summary-label">Paid Amount</span>
                            <div class="summary-value">
                                <div id="paidAmountInput"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= site_url('sells') ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="button" id="saveSaleBtn" class="btn btn-success">Save Sale</button>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        warehouses: "<?= site_url('api/warehouses') ?>",
        warehouseProducts: "<?= site_url('api/warehouse-products') ?>",
        sales: "<?= site_url('api/sales') ?>"
    };

    let stockSource = [];
    let saleItems = [];
    let lastSaleWarehouseId = 0;
    let stockSearchTerm = "";
    let stockDepartmentFilter = "";
    let stockGenderFilter = "";
    let stockSeasonFilter = "";
    let saleTotalsCalcLock = false;

    function initWidgets() {
        $("#saleDateInput").jqxDateTimeInput({ width: "100%", height: 34, formatString: "yyyy-MM-dd" });
        $("#saleWarehouseDropdown").jqxDropDownList({
            width: "100%",
            height: 34,
            displayMember: "name",
            valueMember: "id",
            placeHolder: "Select sale warehouse"
        });
        $("#stockWarehouseDropdown").jqxDropDownList({
            width: "100%",
            height: 34,
            displayMember: "name",
            valueMember: "id",
            placeHolder: "All warehouses"
        });
        $("#discountTotalInput").jqxNumberInput({
            width: "100%",
            height: 34,
            decimalDigits: 2,
            digits: 12,
            min: 0,
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
                { text: "Cost Price", datafield: "cost_price", width: 100, cellsformat: "f2", cellsalign: "right" },
                { text: "Sell Price", datafield: "selling_price", width: 100, cellsformat: "f2", cellsalign: "right" }
            ]
        });

        $("#saleItemsGrid").jqxGrid({
            width: "100%",
            height: 520,
            source: new $.jqx.dataAdapter({ localdata: saleItems, datatype: "array" }),
            editable: true,
            editmode: "click",
            selectionmode: "singlerow",
            columnsresize: true,
            showtoolbar: true,
            showstatusbar: true,
            statusbarheight: 34,
            rendertoolbar: function (toolbar) {
                const container = $('<div class="d-flex align-items-center h-100 px-2"></div>');
                const deleteBtn = $('<button type="button" id="removeSaleItemBtn" class="btn btn-sm btn-outline-danger">Remove Selected</button>');
                container.append(deleteBtn);
                toolbar.append(container);
                container.on("click", "#removeSaleItemBtn", removeSelectedSaleItem);
            },
            renderstatusbar: function (statusbar) {
                const footer = $('<div id="saleItemsTotalsFooter" class="d-flex align-items-center h-100 px-2 small fw-semibold text-secondary"></div>');
                statusbar.append(footer);
                updateSaleTotalsFooter();
            },
            columns: [
                { text: "Product", datafield: "product_name", width: 160, editable: false },
                { text: "SKU", datafield: "sku", width: 110, editable: false },
                { text: "Size", datafield: "size_value", width: 70, editable: false },
                { text: "Qty", datafield: "qty", width: 70, cellsalign: "right", columntype: "numberinput" },
                { text: "Cost Price", datafield: "unit_cost", width: 110, cellsformat: "f2", cellsalign: "right", columntype: "numberinput" },
                { text: "Sell Price", datafield: "unit_price", width: 110, cellsformat: "f2", cellsalign: "right", columntype: "numberinput" },
                { text: "Line Total", datafield: "line_total", width: 110, cellsformat: "f2", cellsalign: "right", editable: false }
            ]
        });

        $("#stockGrid").on("rowclick", function (event) {
            const row = $("#stockGrid").jqxGrid("getrowdata", event.args.rowindex);
            if (row) {
                addOneToSale(row);
            }
        });

        $("#saleItemsGrid").on("cellvaluechanged", function (event) {
            syncSaleItemFromGrid(event.args.rowindex, event.args.datafield);
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
        const source = {
            localdata: getFilteredStockRows(),
            datatype: "array"
        };
        $("#stockGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
    }

    function clearStockSearch() {
        stockSearchTerm = "";
        $("#stockSearchInput").val("");
    }

    function clearStockFilters() {
        clearStockSearch();
        stockDepartmentFilter = "";
        stockGenderFilter = "";
        stockSeasonFilter = "";
        $("#stockDepartmentSelect").val("");
        $("#stockGenderSelect").val("");
        $("#stockSeasonSelect").val("");
        $("#stockWarehouseDropdown").jqxDropDownList("clearSelection");
        loadStock();
    }

    function refreshSaleGrid() {
        $("#saleItemsGrid").jqxGrid({ source: new $.jqx.dataAdapter({ localdata: saleItems, datatype: "array" }) });
        updateSaleTotalsFooter();
    }

    function getSaleSubTotal() {
        return (saleItems || []).reduce((sum, r) => sum + Number(r.line_total || 0), 0);
    }

    function recalcSaleTotals(changedField) {
        if (saleTotalsCalcLock) {
            return null;
        }

        const subTotal = getSaleSubTotal();
        saleTotalsCalcLock = true;

        let discount = Number($("#discountTotalInput").jqxNumberInput("val") || 0);
        let paid = Number($("#paidAmountInput").jqxNumberInput("val") || 0);

        if (changedField === "items") {
            paid = subTotal;
            discount = 0;
            $("#paidAmountInput").jqxNumberInput("val", paid);
            $("#discountTotalInput").jqxNumberInput("val", discount);
        } else if (changedField === "paid") {
            discount = Number(Math.max(0, subTotal - paid).toFixed(2));
            $("#discountTotalInput").jqxNumberInput("val", discount);
        } else if (changedField === "discount") {
            paid = Number(Math.max(0, subTotal - discount).toFixed(2));
            $("#paidAmountInput").jqxNumberInput("val", paid);
        }

        $("#subTotalDisplay").val(subTotal.toFixed(2));
        const grandTotal = Number(Math.max(0, subTotal - discount).toFixed(2));
        $("#grandTotalDisplay").val(grandTotal.toFixed(2));
        saleTotalsCalcLock = false;

        return { subTotal, discount, paid, grandTotal };
    }

    function updateSaleTotalsFooter() {
        const rows = saleItems || [];
        $("#saleItemsCount").text(rows.length);
        const totalQty = rows.reduce((sum, r) => sum + Number(r.qty || 0), 0);
        const totals = recalcSaleTotals("items");
        const grandTotal = totals ? totals.grandTotal : getSaleSubTotal();
        $("#saleItemsTotalsFooter").text(
            `Total Qty: ${totalQty} | Grand Total: ${grandTotal.toFixed(2)}`
        );
    }

    function findStockByVariant(variantId) {
        return stockSource.find(s => Number(s.variant_id) === Number(variantId));
    }

    function remainingQtyForVariant(variantId) {
        return stockSource
            .filter(s => Number(s.variant_id) === Number(variantId))
            .reduce((sum, s) => sum + Number(s.remaining_qty || 0), 0);
    }

    function findSaleItem(variantId) {
        return saleItems.find(s => Number(s.variant_id) === Number(variantId));
    }

    function getZeroSellingPriceItems() {
        return (saleItems || []).filter(function (r) {
            return Number(r.unit_price || 0) <= 0;
        });
    }

    function alertZeroSellingPriceItems() {
        const items = getZeroSellingPriceItems();
        if (items.length === 0) {
            return false;
        }

        const labels = items.map(function (r) {
            const name = String(r.product_name || "").trim();
            const sku = String(r.sku || "").trim();
            return name !== "" ? name : (sku !== "" ? sku : "Unknown product");
        });

        alert(
            "One or more items have selling price 0:\n\n" +
            labels.map(function (label) { return "- " + label; }).join("\n")
        );
        return true;
    }

    function addOneToSale(stockRow) {
        const variantId = Number(stockRow.variant_id);
        const stock = stockSource.find(s => Number(s.inventory_id) === Number(stockRow.inventory_id))
            || findStockByVariant(variantId);
        if (!stock || Number(stock.remaining_qty) < 1) {
            setMessage("No stock available for this product.", true);
            return;
        }

        stock.remaining_qty = Number(stock.remaining_qty) - 1;

        let saleRow = findSaleItem(variantId);
        const unitCost = Number(stock.cost_price || 0);
        const unitPrice = Number(stock.selling_price || 0);
        if (saleRow) {
            saleRow.qty = Number(saleRow.qty) + 1;
            saleRow.line_total = Number((saleRow.qty * saleRow.unit_price).toFixed(2));
        } else {
            saleItems.push({
                variant_id: variantId,
                product_name: stock.product_name || "",
                sku: stock.sku || "",
                size_value: stock.size_value || "",
                unit_cost: unitCost,
                unit_price: unitPrice,
                qty: 1,
                line_total: unitPrice
            });
        }

        refreshStockGrid();
        refreshSaleGrid();
        setMessage("Item added to sale.", false);
    }

    function syncSaleItemFromGrid(rowIndex, datafield) {
        const gridRow = $("#saleItemsGrid").jqxGrid("getrowdata", rowIndex);
        if (!gridRow) {
            return;
        }

        const variantId = Number(gridRow.variant_id);
        const saleRow = findSaleItem(variantId);
        if (!saleRow) {
            return;
        }

        const field = String(datafield || "");

        if (field === "qty") {
            const oldQty = Number(saleRow.qty);
            const newQty = Math.max(1, Number(gridRow.qty || 1));
            const maxQty = oldQty + remainingQtyForVariant(variantId);
            const adjustedQty = Math.min(newQty, maxQty);

            if (adjustedQty !== newQty) {
                setMessage("Qty adjusted to available stock.", true);
            }

            const qtyDelta = adjustedQty - oldQty;
            adjustVariantRemainingQty(variantId, -qtyDelta);
            saleRow.qty = adjustedQty;
        } else if (field === "unit_cost") {
            saleRow.unit_cost = Number(Number(gridRow.unit_cost || 0).toFixed(2));
        } else if (field === "unit_price") {
            saleRow.unit_price = Number(Number(gridRow.unit_price || 0).toFixed(2));
        }

        saleRow.line_total = Number((Number(saleRow.qty) * Number(saleRow.unit_price)).toFixed(2));

        refreshStockGrid();
        refreshSaleGrid();
    }

    function adjustVariantRemainingQty(variantId, delta) {
        let change = Number(delta);
        if (change === 0) {
            return;
        }

        for (const stock of stockSource) {
            if (Number(stock.variant_id) !== Number(variantId)) {
                continue;
            }
            if (change < 0) {
                const take = Math.min(Number(stock.remaining_qty), -change);
                stock.remaining_qty = Number(stock.remaining_qty) - take;
                change += take;
            } else {
                stock.remaining_qty = Number(stock.remaining_qty) + change;
                change = 0;
            }
            if (change === 0) {
                break;
            }
        }
    }

    function removeSelectedSaleItem() {
        const index = $("#saleItemsGrid").jqxGrid("getselectedrowindex");
        if (index < 0) {
            setMessage("Select a sale item row to remove.", true);
            return;
        }

        const row = $("#saleItemsGrid").jqxGrid("getrowdata", index);
        if (!row) {
            return;
        }

        adjustVariantRemainingQty(row.variant_id, Number(row.qty || 0));

        saleItems = saleItems.filter(s => Number(s.variant_id) !== Number(row.variant_id));
        refreshStockGrid();
        refreshSaleGrid();
        setMessage("Item removed from sale.", false);
    }

    function loadWarehouses() {
        return $.getJSON(API_URLS.warehouses).done(function(res) {
            const rows = res.data || [];
            $("#saleWarehouseDropdown").jqxDropDownList({ source: rows });
            $("#stockWarehouseDropdown").jqxDropDownList({ source: rows });
            $("#saleWarehouseDropdown").jqxDropDownList("clearSelection");
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
                    selling_price: Number(p.selling_price || 0),
                    original_qty: qty,
                    remaining_qty: qty
                };
            });
            for (const item of saleItems) {
                adjustVariantRemainingQty(item.variant_id, -Number(item.qty || 0));
            }
            refreshStockGrid();
            if (!stockSource.length) {
                setMessage("No stock found.", true);
            }
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to load stock.", true);
        });
    }

    function saveSale() {
        const warehouseItem = $("#saleWarehouseDropdown").jqxDropDownList("getSelectedItem");
        const warehouseId = warehouseItem ? Number(warehouseItem.value) : 0;
        if (!warehouseId) {
            setMessage("Please select sale warehouse.", true);
            return;
        }
        if (saleItems.length === 0) {
            setMessage("Add at least one item.", true);
            return;
        }

        if (alertZeroSellingPriceItems()) {
            return;
        }

        const saleDate = $("#saleDateInput").jqxDateTimeInput("getText");
        if (!saleDate) {
            setMessage("Sale date is required.", true);
            return;
        }

        const payload = {
            warehouse_id: warehouseId,
            sale_date: saleDate,
            customer_name: String($("#customerNameInput").val() || "").trim(),
            discount_total: Number($("#discountTotalInput").jqxNumberInput("val") || 0),
            paid_total: Number($("#paidAmountInput").jqxNumberInput("val") || 0),
            payment_method: String($("#paymentMethodSelect").val() || "cash"),
            items: saleItems.map(r => ({
                variant_id: Number(r.variant_id),
                qty: Number(r.qty),
                unit_price: Number(r.unit_price)
            }))
        };

        $("#saveSaleBtn").prop("disabled", true);
        $.ajax({
            url: API_URLS.sales,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload)
        }).done(function(res) {
            setMessage(res.message || "Sale saved.", false);
            setTimeout(function () {
                window.location.href = "<?= site_url('sells') ?>";
            }, 500);
        }).fail(function(xhr) {
            const msg = xhr.responseJSON?.error
                ? `${xhr.responseJSON.message}: ${xhr.responseJSON.error}`
                : (xhr.responseJSON?.message || "Failed to save sale.");
            setMessage(msg, true);
        }).always(function () {
            $("#saveSaleBtn").prop("disabled", false);
        });
    }

    $(function() {
        initWidgets();
        loadWarehouses().done(loadStock);
        $("#stockWarehouseDropdown").on("select", function () {
            loadStock();
        });

        $("#saveSaleBtn").on("click", saveSale);
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
        $("#discountTotalInput").on("valueChanged", function () {
            recalcSaleTotals("discount");
        });
        $("#paidAmountInput").on("valueChanged", function () {
            recalcSaleTotals("paid");
        });
        recalcSaleTotals("items");
    });
</script>
<?= $this->endSection() ?>
