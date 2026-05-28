<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Inventory<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Inventory</h1>
                <p class="text-muted mb-0">Track current stock by product variant.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold mb-3">Inventory List</h2>
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <input type="text" id="filterProductName" class="form-control" placeholder="Filter by product name">
                    </div>
                    <div class="col-12 col-md-4">
                        <input type="text" id="filterProductNumber" class="form-control" placeholder="Filter by serial number">
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="applyInventoryFilterBtn" class="btn btn-primary">Filter</button>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="resetInventoryFilterBtn" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
                <div id="inventoryGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            inventory: "<?= site_url('api/inventory') ?>"
        };

        function initWidgets() {
            $("#inventoryGrid").jqxGrid({
                width: "100%",
                height: 520,
                columnsresize: true,
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

        function loadInventory() {
            const productName = String($("#filterProductName").val() || "").trim();
            const productNumber = String($("#filterProductNumber").val() || "").trim();
            const params = {};
            if (productName) {
                params.product_name = productName;
            }
            if (productNumber) {
                params.product_number = productNumber;
            }

            return $.getJSON(API_URLS.inventory, params).done(function(res) {
                const source = { localdata: res.data || [], datatype: "array" };
                $("#inventoryGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load inventory.";
                console.error(msg);
            });
        }

        $(function() {
            initWidgets();
            loadInventory();
            $("#applyInventoryFilterBtn").on("click", loadInventory);
            $("#resetInventoryFilterBtn").on("click", function () {
                $("#filterProductName").val("");
                $("#filterProductNumber").val("");
                loadInventory();
            });
            $("#filterProductName, #filterProductNumber").on("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    loadInventory();
                }
            });
        });
</script>
<?= $this->endSection() ?>
