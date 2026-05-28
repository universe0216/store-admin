<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>New Sale<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">New Sale</h1>
                <p class="text-muted mb-0">Create a sale transaction.</p>
            </div>
            <a href="<?= site_url('sells') ?>" class="btn btn-outline-secondary">Back to Sales</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-secondary mb-1">Sale Date</label>
                        <div id="saleDateInput"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-secondary mb-1">Warehouse</label>
                        <div id="warehouseDropdown"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-secondary mb-1">Customer Name</label>
                        <input type="text" id="customerNameInput" class="form-control" placeholder="Optional">
                    </div>
                </div>

                <div class="border rounded-3 p-3 mb-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary mb-1">Product</label>
                            <div id="productDropdown"></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Qty</label>
                            <div id="saleQtyInput"></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Unit Price</label>
                            <div id="salePriceInput"></div>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="button" id="addSaleItemBtn" class="btn btn-primary w-100">Add</button>
                        </div>
                    </div>
                </div>

                <div id="saleItemsGrid" class="mb-3"></div>

                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="saveSaleBtn" class="btn btn-success">Save Sale</button>
                    <div id="saleMessageBox" class="small fw-semibold"></div>
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
        sales: "<?= site_url('api/sales') ?>"
    };

    let products = [];

    function setMessage(msg, isError = false) {
        const box = $("#saleMessageBox");
        box.text(msg || "");
        box.removeClass("text-success text-danger");
        box.addClass(isError ? "text-danger" : "text-success");
    }

    function initWidgets() {
        $("#saleDateInput").jqxDateTimeInput({ width: 240, height: 34, formatString: "yyyy-MM-dd HH:mm:ss" });
        $("#warehouseDropdown").jqxDropDownList({ width: 320, height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select warehouse" });
        $("#productDropdown").jqxDropDownList({ width: "100%", height: 34, displayMember: "label", valueMember: "variant_id", placeHolder: "Select product" });
        $("#saleQtyInput").jqxNumberInput({ width: "100%", height: 34, decimalDigits: 0, digits: 8, min: 1, inputMode: "simple", spinButtons: true, value: 1 });
        $("#salePriceInput").jqxNumberInput({ width: "100%", height: 34, decimalDigits: 2, digits: 12, min: 0, inputMode: "simple", spinButtons: true, value: 0 });
        $("#addSaleItemBtn").jqxButton({ width: "100%", height: 34, theme: "base" });
        $("#saveSaleBtn").jqxButton({ width: 140, height: 34, theme: "base" });

        $("#saleItemsGrid").jqxGrid({
            width: "100%",
            height: 300,
            source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
            columnsresize: true,
            selectionmode: "singlerow",
            columns: [
                { text: "Variant ID", datafield: "variant_id", width: 90 },
                { text: "Product", datafield: "product_name", width: 220 },
                { text: "SKU", datafield: "sku", width: 150 },
                { text: "Warehouse Qty", datafield: "available_qty", width: 120, cellsalign: "right" },
                { text: "Qty", datafield: "qty", width: 80, cellsalign: "right" },
                { text: "Unit Price", datafield: "unit_price", width: 120, cellsformat: "f2", cellsalign: "right" },
                { text: "Line Total", datafield: "line_total", cellsformat: "f2", cellsalign: "right" }
            ]
        });
    }

    function getGridRows() {
        return $("#saleItemsGrid").jqxGrid("getrows") || [];
    }

    function loadWarehouses() {
        return $.getJSON(API_URLS.warehouses).done(function(res) {
            const rows = res.data || [];
            $("#warehouseDropdown").jqxDropDownList({ source: rows });
            if (rows.length > 0) {
                $("#warehouseDropdown").jqxDropDownList("selectIndex", 0);
            }
        });
    }

    function loadProductsByWarehouse() {
        const selected = $("#warehouseDropdown").jqxDropDownList("getSelectedItem");
        const warehouseId = selected ? Number(selected.value) : 0;
        if (!warehouseId) {
            products = [];
            $("#productDropdown").jqxDropDownList({ source: [] });
            return;
        }

        $.getJSON(API_URLS.warehouseProducts, { warehouse_id: warehouseId }).done(function(res) {
            products = res.data || [];
            const source = products.map(p => ({
                ...p,
                label: `${p.product_name || "-"} | ${p.sku || "-"} | Stock: ${p.quantity || 0}`
            }));
            $("#productDropdown").jqxDropDownList({ source });
            if (source.length > 0) {
                $("#productDropdown").jqxDropDownList("selectIndex", 0);
            }
        }).fail(function(xhr) {
            setMessage(xhr.responseJSON?.message || "Failed to load warehouse products.", true);
        });
    }

    function addSaleItem() {
        const selectedItem = $("#productDropdown").jqxDropDownList("getSelectedItem");
        if (!selectedItem) {
            setMessage("Please select product.", true);
            return;
        }

        const product = products.find(p => Number(p.variant_id) === Number(selectedItem.value));
        if (!product) {
            setMessage("Selected product is invalid.", true);
            return;
        }

        const qty = Number($("#saleQtyInput").jqxNumberInput("val") || 0);
        const unitPrice = Number($("#salePriceInput").jqxNumberInput("val") || 0);
        if (qty < 1) {
            setMessage("Qty must be at least 1.", true);
            return;
        }
        if (qty > Number(product.quantity || 0)) {
            setMessage("Qty exceeds warehouse stock.", true);
            return;
        }

        const rows = getGridRows();
        if (rows.some(r => Number(r.variant_id) === Number(product.variant_id))) {
            setMessage("Product already added. Remove and add again with new qty/price.", true);
            return;
        }

        const row = {
            variant_id: Number(product.variant_id),
            product_name: product.product_name || "",
            sku: product.sku || "",
            available_qty: Number(product.quantity || 0),
            qty,
            unit_price: Number(unitPrice.toFixed(2)),
            line_total: Number((qty * unitPrice).toFixed(2))
        };
        $("#saleItemsGrid").jqxGrid("addrow", null, row);
        setMessage("Item added.");
    }

    function saveSale() {
        const warehouseItem = $("#warehouseDropdown").jqxDropDownList("getSelectedItem");
        const warehouseId = warehouseItem ? Number(warehouseItem.value) : 0;
        if (!warehouseId) {
            setMessage("Please select warehouse.", true);
            return;
        }
        const rows = getGridRows();
        if (rows.length === 0) {
            setMessage("Add at least one item.", true);
            return;
        }

        const payload = {
            warehouse_id: warehouseId,
            sale_date: $("#saleDateInput").jqxDateTimeInput("getText"),
            customer_name: String($("#customerNameInput").val() || "").trim(),
            items: rows.map(r => ({
                variant_id: Number(r.variant_id),
                qty: Number(r.qty),
                unit_price: Number(r.unit_price)
            }))
        };

        $.ajax({
            url: API_URLS.sales,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload)
        }).done(function(res) {
            setMessage(res.message || "Sale saved.");
            setTimeout(function () {
                window.location.href = "<?= site_url('sells') ?>";
            }, 500);
        }).fail(function(xhr) {
            const msg = xhr.responseJSON?.error
                ? `${xhr.responseJSON.message}: ${xhr.responseJSON.error}`
                : (xhr.responseJSON?.message || "Failed to save sale.");
            setMessage(msg, true);
        });
    }

    $(function() {
        initWidgets();
        loadWarehouses().done(loadProductsByWarehouse);
        $("#warehouseDropdown").on("select", loadProductsByWarehouse);
        $("#addSaleItemBtn").on("click", addSaleItem);
        $("#saveSaleBtn").on("click", saveSale);
    });
</script>
<?= $this->endSection() ?>
