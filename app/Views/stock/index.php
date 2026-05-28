<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Stock<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Stock</h1>
                <p class="text-muted mb-0">Show all stocks.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold mb-3">Stock List</h2>
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <input type="text" id="filterProductName" class="form-control" placeholder="Filter by product name">
                    </div>
                    <div class="col-12 col-md-4">
                        <input type="text" id="filterProductNumber" class="form-control" placeholder="Filter by serial number">
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="applyStockFilterBtn" class="btn btn-primary">Filter</button>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="resetStockFilterBtn" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label text-secondary mb-1">Move To Warehouse</label>
                        <div id="moveWarehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label text-secondary mb-1">Move Qty</label>
                        <div id="moveQtyInput"></div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="moveStockBtn" class="btn btn-warning">Move Selected Variant</button>
                    </div>
                    <div class="col-12 col-md">
                        <div id="stockMessageBox" class="small fw-semibold"></div>
                    </div>
                </div>
                <div id="stockGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            stock: "<?= site_url('api/stock') ?>",
            warehouses: "<?= site_url('api/warehouses') ?>",
            moveWarehouse: "<?= site_url('api/stock/warehouse') ?>"
        };
        let warehouses = [];

        function setMessage(msg, isError = false) {
            const box = $("#stockMessageBox");
            box.text(msg || "");
            box.removeClass("text-success text-danger");
            box.addClass(isError ? "text-danger" : "text-success");
        }

        function initWidgets() {
            $("#moveWarehouseDropdown").jqxDropDownList({ width: "100%", height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select warehouse" });
            $("#moveQtyInput").jqxNumberInput({ width: "100%", height: 34, decimalDigits: 0, digits: 8, min: 1, inputMode: "simple", spinButtons: true, value: 1 });
            $("#stockGrid").jqxGrid({
                width: "100%",
                height: 520,
                columnsresize: true,
                selectionmode: "singlerow",
                filterable: true,
                showfilterrow: true,
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
                columns: [
                    { text: "Inventory ID", datafield: "id", width: 90 },
                    { text: "Variant ID", datafield: "variant_id", width: 90 },
                    { text: "Product", datafield: "product_name", width: 220 },
                    { text: "Product Number", datafield: "product_number", width: 150 },
                    { text: "Brand", datafield: "brand", width: 120 },
                    { text: "Style", datafield: "style", width: 180 },
                    { text: "Size", datafield: "size_value", width: 90 },
                    { text: "SKU", datafield: "sku", width: 150 },
                    { text: "Warehouse", datafield: "warehouse_name", width: 140 },
                    { text: "Location", datafield: "warehouse_location", width: 150 },
                    { text: "Qty", datafield: "quantity", width: 90, cellsalign: "right" },
                    { text: "Reserved", datafield: "reserved_quantity", width: 90, cellsalign: "right" },
                    { text: "Cost Price", datafield: "cost_price", width: 110, cellsformat: "f2", cellsalign: "right" },
                    { text: "Selling Price", datafield: "selling_price", cellsformat: "f2", cellsalign: "right" }
                ]
            });
        }

        function loadWarehouses() {
            return $.getJSON(API_URLS.warehouses).done(function(res) {
                warehouses = res.data || [];
                $("#moveWarehouseDropdown").jqxDropDownList({ source: warehouses });
            }).fail(function(xhr) {
                setMessage(xhr.responseJSON?.message || "Failed to load warehouses.", true);
            });
        }

        function loadStock() {
            const productName = String($("#filterProductName").val() || "").trim();
            const productNumber = String($("#filterProductNumber").val() || "").trim();
            const params = {};
            if (productName) {
                params.product_name = productName;
            }
            if (productNumber) {
                params.product_number = productNumber;
            }

            return $.getJSON(API_URLS.stock, params).done(function(res) {
                const source = { localdata: res.data || [], datatype: "array" };
                $("#stockGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load stocks.";
                setMessage(msg, true);
            });
        }

        function moveSelectedVariant() {
            const rowIndex = $("#stockGrid").jqxGrid("getselectedrowindex");
            if (rowIndex === -1) {
                setMessage("Select a stock row first.", true);
                return;
            }

            const row = $("#stockGrid").jqxGrid("getrowdata", rowIndex);
            const targetWarehouse = $("#moveWarehouseDropdown").jqxDropDownList("getSelectedItem");
            const toWarehouseId = Number(targetWarehouse?.value || 0);
            const qty = Number($("#moveQtyInput").jqxNumberInput("val") || 0);

            if (!toWarehouseId) {
                setMessage("Select destination warehouse.", true);
                return;
            }
            if (qty < 1) {
                setMessage("Move qty must be at least 1.", true);
                return;
            }
            if (Number(row.warehouse_id) === toWarehouseId) {
                setMessage("Choose a different warehouse.", true);
                return;
            }

            $.ajax({
                url: API_URLS.moveWarehouse,
                method: "PUT",
                contentType: "application/json",
                data: JSON.stringify({
                    variant_id: Number(row.variant_id || 0),
                    from_warehouse_id: Number(row.warehouse_id || 0),
                    to_warehouse_id: toWarehouseId,
                    qty
                })
            }).done(function(res) {
                setMessage(res.message || "Stock moved.");
                loadStock();
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.error
                    ? `${xhr.responseJSON.message}: ${xhr.responseJSON.error}`
                    : (xhr.responseJSON?.message || "Failed to move stock.");
                setMessage(msg, true);
            });
        }

        $(function() {
            initWidgets();
            loadWarehouses();
            loadStock();
            $("#applyStockFilterBtn").on("click", loadStock);
            $("#moveStockBtn").on("click", moveSelectedVariant);
            $("#resetStockFilterBtn").on("click", function () {
                $("#filterProductName").val("");
                $("#filterProductNumber").val("");
                loadStock();
            });
            $("#filterProductName, #filterProductNumber").on("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    loadStock();
                }
            });
        });
</script>
<?= $this->endSection() ?>
