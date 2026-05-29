<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>New Transfer<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    #transferHeaderForm .jqx-numberinput input {
        height: 100% !important;
        line-height: 34px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    #stockGrid .jqx-grid-cell-hover {
        cursor: pointer;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid py-4 px-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">New Transfer</h1>
            </div>
            <a href="<?= site_url('transfers') ?>" class="btn btn-outline-secondary">Back to Transfers</a>
        </div>

        <div id="transferHeaderForm" class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Transfer Date</label>
                        <div id="transferDateInput"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">From Warehouse</label>
                        <div id="fromWarehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">To Warehouse</label>
                        <div id="toWarehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label text-secondary mb-1">Notes</label>
                        <input type="text" id="transferNotesInput" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-12 d-flex align-items-end justify-content-lg-end">
                        <button type="button" id="saveTransferBtn" class="btn btn-success">Save Transfer</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="transferMessageBox" class="small fw-semibold mb-3"></div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <input type="text" id="stockSearchInput" class="form-control" placeholder="Search by product name or product number">
                                    <button type="button" id="clearStockFiltersBtn" class="btn btn-outline-secondary btn-sm text-nowrap">Clear</button>
                                </div>
                            </div>
                        </div>
                        <div id="stockGrid"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Transfer Items</h2>
                        <div id="transferItemsGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    const API_URLS = {
        warehouses: "<?= site_url('api/warehouses') ?>",
        warehouseProducts: "<?= site_url('api/warehouse-products') ?>",
        transfers: "<?= site_url('api/transfers') ?>"
    };

    let stockSource = [];
    let transferItems = [];
    let lastFromWarehouseId = 0;
    let stockSearchTerm = "";

    function setMessage(msg, isError = false) {
        const box = $("#transferMessageBox");
        box.text(msg || "");
        box.removeClass("text-success text-danger");
        box.addClass(isError ? "text-danger" : "text-success");
    }

    function initWidgets() {
        $("#transferDateInput").jqxDateTimeInput({ width: "100%", height: 34, formatString: "yyyy-MM-dd" });
        $("#fromWarehouseDropdown").jqxDropDownList({
            width: "100%",
            height: 34,
            displayMember: "name",
            valueMember: "id",
            placeHolder: "Select source warehouse"
        });
        $("#toWarehouseDropdown").jqxDropDownList({
            width: "100%",
            height: 34,
            displayMember: "name",
            valueMember: "id",
            placeHolder: "Select destination warehouse"
        });
        $("#saveTransferBtn").jqxButton({ width: 160, height: 38, theme: "base" });

        $("#stockGrid").jqxGrid({
            width: "100%",
            height: 520,
            source: new $.jqx.dataAdapter({ localdata: stockSource, datatype: "array" }),
            selectionmode: "singlerow",
            columnsresize: true,
            enablehover: true,
            columns: [
                { text: "Product", datafield: "product_name", width: 140 },
                { text: "Product No.", datafield: "product_number", width: 100 },
                { text: "Style", datafield: "style", width: 90 },
                { text: "Size", datafield: "size_value", width: 70 },
                { text: "Brand", datafield: "brand", width: 90 },
                { text: "Available", datafield: "remaining_qty", width: 90, cellsalign: "right" },
                { text: "Cost Price", datafield: "cost_price", width: 100, cellsformat: "f2", cellsalign: "right" }
            ]
        });

        $("#transferItemsGrid").jqxGrid({
            width: "100%",
            height: 520,
            source: new $.jqx.dataAdapter({ localdata: transferItems, datatype: "array" }),
            editable: true,
            editmode: "click",
            selectionmode: "singlerow",
            columnsresize: true,
            showtoolbar: true,
            showstatusbar: true,
            statusbarheight: 34,
            rendertoolbar: function (toolbar) {
                const container = $('<div class="d-flex align-items-center h-100 px-2"></div>');
                const deleteBtn = $('<button type="button" id="removeTransferItemBtn" class="btn btn-sm btn-outline-danger">Remove Selected</button>');
                container.append(deleteBtn);
                toolbar.append(container);
                container.on("click", "#removeTransferItemBtn", removeSelectedTransferItem);
            },
            renderstatusbar: function (statusbar) {
                const footer = $('<div id="transferItemsTotalsFooter" class="d-flex align-items-center h-100 px-2 small fw-semibold text-secondary"></div>');
                statusbar.append(footer);
                updateTransferTotalsFooter();
            },
            columns: [
                { text: "Product", datafield: "product_name", width: 180, editable: false },
                { text: "SKU", datafield: "sku", width: 110, editable: false },
                { text: "Size", datafield: "size_value", width: 70, editable: false },
                { text: "Qty", datafield: "qty", width: 80, cellsalign: "right", columntype: "numberinput" }
            ]
        });

        $("#stockGrid").on("rowclick", function (event) {
            const row = $("#stockGrid").jqxGrid("getrowdata", event.args.rowindex);
            if (row) {
                addOneToTransfer(row);
            }
        });

        $("#transferItemsGrid").on("cellvaluechanged", function (event) {
            syncTransferItemFromGrid(event.args.rowindex, event.args.datafield);
        });
    }

    function getFilteredStockRows() {
        const term = stockSearchTerm.trim().toLowerCase();

        return stockSource.filter(r => {
            if (Number(r.remaining_qty) <= 0) {
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
        refreshStockGrid();
    }

    function refreshTransferGrid() {
        $("#transferItemsGrid").jqxGrid({ source: new $.jqx.dataAdapter({ localdata: transferItems, datatype: "array" }) });
        updateTransferTotalsFooter();
    }

    function getTransferTotalQty() {
        return (transferItems || []).reduce((sum, r) => sum + Number(r.qty || 0), 0);
    }

    function updateTransferTotalsFooter() {
        const totalQty = getTransferTotalQty();
        $("#transferItemsTotalsFooter").text(`Total Qty: ${totalQty}`);
    }

    function findStockByVariant(variantId) {
        return stockSource.find(s => Number(s.variant_id) === Number(variantId));
    }

    function remainingQtyForVariant(variantId) {
        return stockSource
            .filter(s => Number(s.variant_id) === Number(variantId))
            .reduce((sum, s) => sum + Number(s.remaining_qty || 0), 0);
    }

    function findTransferItem(variantId) {
        return transferItems.find(s => Number(s.variant_id) === Number(variantId));
    }

    function addOneToTransfer(stockRow) {
        const variantId = Number(stockRow.variant_id);
        const stock = stockSource.find(s => Number(s.inventory_id) === Number(stockRow.inventory_id))
            || findStockByVariant(variantId);
        if (!stock || Number(stock.remaining_qty) < 1) {
            setMessage("No stock available for this product.", true);
            return;
        }

        stock.remaining_qty = Number(stock.remaining_qty) - 1;

        let transferRow = findTransferItem(variantId);
        if (transferRow) {
            transferRow.qty = Number(transferRow.qty) + 1;
        } else {
            transferItems.push({
                variant_id: variantId,
                product_name: stock.product_name || "",
                sku: stock.sku || "",
                size_value: stock.size_value || "",
                qty: 1
            });
        }

        refreshStockGrid();
        refreshTransferGrid();
        setMessage("Item added to transfer.");
    }

    function syncTransferItemFromGrid(rowIndex, datafield) {
        const gridRow = $("#transferItemsGrid").jqxGrid("getrowdata", rowIndex);
        if (!gridRow) {
            return;
        }

        const variantId = Number(gridRow.variant_id);
        const transferRow = findTransferItem(variantId);
        if (!transferRow) {
            return;
        }

        if (String(datafield || "") === "qty") {
            const oldQty = Number(transferRow.qty);
            const newQty = Math.max(1, Number(gridRow.qty || 1));
            const maxQty = oldQty + remainingQtyForVariant(variantId);
            const adjustedQty = Math.min(newQty, maxQty);

            if (adjustedQty !== newQty) {
                setMessage("Qty adjusted to available stock.", true);
            }

            const qtyDelta = adjustedQty - oldQty;
            adjustVariantRemainingQty(variantId, -qtyDelta);
            transferRow.qty = adjustedQty;
        }

        refreshStockGrid();
        refreshTransferGrid();
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

    function removeSelectedTransferItem() {
        const index = $("#transferItemsGrid").jqxGrid("getselectedrowindex");
        if (index < 0) {
            setMessage("Select a transfer item row to remove.", true);
            return;
        }

        const row = $("#transferItemsGrid").jqxGrid("getrowdata", index);
        if (!row) {
            return;
        }

        adjustVariantRemainingQty(row.variant_id, Number(row.qty || 0));

        transferItems = transferItems.filter(s => Number(s.variant_id) !== Number(row.variant_id));
        refreshStockGrid();
        refreshTransferGrid();
        setMessage("Item removed from transfer.");
    }

    function loadWarehouses() {
        return $.getJSON(API_URLS.warehouses).done(function(res) {
            const rows = res.data || [];
            $("#fromWarehouseDropdown").jqxDropDownList({ source: rows });
            $("#toWarehouseDropdown").jqxDropDownList({ source: rows });
            $("#fromWarehouseDropdown").jqxDropDownList("clearSelection");
            $("#toWarehouseDropdown").jqxDropDownList("clearSelection");
        });
    }

    function getFromWarehouseId() {
        const selected = $("#fromWarehouseDropdown").jqxDropDownList("getSelectedItem");
        return selected ? Number(selected.value) : 0;
    }

    function loadStock() {
        const warehouseId = getFromWarehouseId();
        if (!warehouseId) {
            stockSource = [];
            refreshStockGrid();
            setMessage("Select a source warehouse to load stock.", true);
            return;
        }

        $.getJSON(API_URLS.warehouseProducts, { warehouse_id: warehouseId }).done(function(res) {
            stockSource = (res.data || []).map(p => {
                const qty = Number(p.quantity || 0);
                return {
                    inventory_id: Number(p.inventory_id || 0),
                    variant_id: Number(p.variant_id),
                    warehouse_id: Number(p.warehouse_id || 0),
                    product_name: p.product_name || "",
                    product_number: p.product_number || "",
                    sku: p.sku || "",
                    style: p.style || "",
                    size_value: p.size_value || "",
                    brand: p.brand || "",
                    cost_price: Number(p.cost_price || 0),
                    original_qty: qty,
                    remaining_qty: qty
                };
            });
            for (const item of transferItems) {
                adjustVariantRemainingQty(item.variant_id, -Number(item.qty || 0));
            }
            refreshStockGrid();
            setMessage(stockSource.length ? "" : "No stock found in selected warehouse.");
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to load stock.", true);
        });
    }

    function saveTransfer() {
        const fromWarehouseId = getFromWarehouseId();
        const toItem = $("#toWarehouseDropdown").jqxDropDownList("getSelectedItem");
        const toWarehouseId = toItem ? Number(toItem.value) : 0;

        if (!fromWarehouseId) {
            setMessage("Please select source warehouse.", true);
            return;
        }
        if (!toWarehouseId) {
            setMessage("Please select destination warehouse.", true);
            return;
        }
        if (fromWarehouseId === toWarehouseId) {
            setMessage("Source and destination warehouses must be different.", true);
            return;
        }
        if (transferItems.length === 0) {
            setMessage("Add at least one item.", true);
            return;
        }

        const transferDate = $("#transferDateInput").jqxDateTimeInput("getText");
        if (!transferDate) {
            setMessage("Transfer date is required.", true);
            return;
        }

        const payload = {
            from_warehouse_id: fromWarehouseId,
            to_warehouse_id: toWarehouseId,
            transfer_date: transferDate,
            notes: String($("#transferNotesInput").val() || "").trim(),
            items: transferItems.map(r => ({
                variant_id: Number(r.variant_id),
                qty: Number(r.qty)
            }))
        };

        $("#saveTransferBtn").prop("disabled", true);
        $.ajax({
            url: API_URLS.transfers,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload)
        }).done(function(res) {
            setMessage(res.message || "Transfer saved.");
            setTimeout(function () {
                window.location.href = "<?= site_url('transfers') ?>";
            }, 500);
        }).fail(function(xhr) {
            const msg = xhr.responseJSON?.error
                ? `${xhr.responseJSON.message}: ${xhr.responseJSON.error}`
                : (xhr.responseJSON?.message || "Failed to save transfer.");
            setMessage(msg, true);
        }).always(function () {
            $("#saveTransferBtn").prop("disabled", false);
        });
    }

    $(function() {
        initWidgets();
        loadWarehouses();
        $("#fromWarehouseDropdown").on("select", function (event) {
            const newWarehouseId = Number(event.args?.item?.value || 0);
            if (transferItems.length > 0 && newWarehouseId !== lastFromWarehouseId &&
                !confirm("Changing source warehouse will clear transfer items. Continue?")) {
                const items = $("#fromWarehouseDropdown").jqxDropDownList("getItems") || [];
                const prevIndex = items.findIndex(item => Number(item.value) === lastFromWarehouseId);
                if (prevIndex >= 0) {
                    $("#fromWarehouseDropdown").jqxDropDownList("selectIndex", prevIndex);
                } else {
                    $("#fromWarehouseDropdown").jqxDropDownList("clearSelection");
                }
                return;
            }
            if (newWarehouseId !== lastFromWarehouseId) {
                transferItems = [];
                refreshTransferGrid();
            }
            lastFromWarehouseId = newWarehouseId;
            loadStock();
        });
        $("#fromWarehouseDropdown").on("unselect", function () {
            lastFromWarehouseId = 0;
            stockSource = [];
            transferItems = [];
            refreshStockGrid();
            refreshTransferGrid();
        });
        $("#saveTransferBtn").on("click", saveTransfer);
        $("#stockSearchInput").on("input", function () {
            stockSearchTerm = String($(this).val() || "");
            refreshStockGrid();
        });
        $("#clearStockFiltersBtn").on("click", clearStockFilters);
    });
</script>
<?= $this->endSection() ?>
