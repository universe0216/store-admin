<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>New Purchase<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    #registerProductDetailsForm .jqx-numberinput input {
        height: 100% !important;
        line-height: 34px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">New Purchase</h1>
                <p class="text-muted mb-0">Create purchase transaction.</p>
            </div>
            <a href="<?= site_url('purchases') ?>" class="btn btn-outline-secondary">Back to List</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-secondary mb-1">Purchase Date</label>
                        <div id="purchaseDate"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-secondary mb-1">Supplier</label>
                        <div id="supplierDropdown"></div>
                    </div>
                </div>

                <div class="border rounded-3 p-3 mb-4 bg-light">
                    <h2 class="h6 fw-semibold mb-3">Quick Create Product</h2>
                    <form id="quickProductForm" class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Name</label>
                            <input type="text" id="productNameInput" class="form-control" placeholder="Product name">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Serial Number</label>
                            <input type="text" id="productSerialInput" class="form-control" placeholder="Serial number">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Category</label>
                            <div id="categoryDropDownButton" style="width: 100%;">
                                <div id="categoryTree"></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Brand</label>
                            <input type="text" id="productBrandInput" class="form-control" placeholder="Brand">
                        </div>
                        <div class="col-12">
                            <button type="submit" id="registerProductBtn" class="btn btn-sm btn-success">Register Product</button>
                            <div id="quickProductMessage" class="small fw-semibold mt-2"></div>
                        </div>
                    </form>
                </div>

                <div class="border rounded-3 p-3 mb-4">
                    <h2 class="h6 fw-semibold mb-3">Register Product Details</h2>
                    <form id="registerProductDetailsForm" class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary mb-1">Product</label>
                            <div id="productDropdown"></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary mb-1">Style</label>
                            <input type="text" id="productStyleInput" class="form-control" placeholder="Style">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary mb-1">Size</label>
                            <div id="sizeSelector"></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary mb-1">Warehouse</label>
                            <div id="purchaseWarehouseDropdown"></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Sets Count</label>
                            <div id="setsCountInput"></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Unit Price</label>
                            <div id="unitPriceInput"></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Total Units</label>
                            <div id="totalUnitsInput"></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-secondary mb-1">Total Price</label>
                            <div id="totalPriceInput"></div>
                        </div>
                        <div class="col-12">
                            <button type="button" id="addProductsBtn" class="btn btn-sm btn-primary">Add producs</button>
                        </div>
                    </form>
                </div>

                <div id="itemsGrid" class="mb-3"></div>

                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-secondary mb-1">Notes</label>
                        <input id="notesInput" type="text">
                    </div>
                    <div class="col-12 col-md-auto">
                        <input type="button" id="savePurchaseBtn" value="Save Purchase">
                    </div>
                    <div class="col-12 col-md">
                        <div id="messageBox" class="small fw-semibold text-success"></div>
                    </div>
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
                    <div>Sum Total Price: <span id="confirmTotalPriceSum" class="fw-semibold">0.00</span></div>
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
            variants: "<?= site_url('api/product-variants') ?>",
            warehouses: "<?= site_url('api/warehouses') ?>",
            purchases: "<?= site_url('api/purchases') ?>"
        };

        const items = [];
        let suppliers = [];
        let categories = [];
        let products = [];
        let warehouses = [];
        let productVariants = [];
        let selectedSizeCount = 0;
        let selectedCategoryId = 0;
        let confirmPurchaseModal = null;
        const FIXED_SIZES = [220, 225, 230, 235, 240, 245, 250].map(size => ({
            label: String(size),
            value: String(size)
        }));

        function initWidgets() {
            $("#supplierDropdown").jqxDropDownList({ width: 280, height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select supplier" });
            $("#purchaseDate").jqxDateTimeInput({ width: 220, height: 34, formatString: "yyyy-MM-dd HH:mm:ss" });
            $("#categoryDropDownButton").jqxDropDownButton({ width: "100%", height: 34 });
            $("#categoryDropDownButton").jqxDropDownButton("setContent", '<div style="position: relative; margin-left: 3px; margin-top: 5px;" class="text-muted">Select category</div>');
            $("#productDropdown").jqxDropDownList({ width: 280, height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select product" });
            $("#purchaseWarehouseDropdown").jqxDropDownList({ width: "100%", height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select warehouse" });
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
            $("#unitPriceInput").jqxNumberInput({
                width: "100%",
                height: 34,
                decimalDigits: 2,
                digits: 10,
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
            $("#notesInput").jqxInput({ width: 420, height: 34 });
            $("#savePurchaseBtn").jqxButton({ width: 140, height: 34, theme: "base" });
            $("#addProductsBtn").jqxButton({ width: 140, height: 34, theme: "base" });

            $("#itemsGrid").jqxGrid({
                width: "100%",
                height: 220,
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
            $("#itemsGridTotalsFooter").text(
                `Total Counts: ${totals.totalCount} | Sum Total Price: ${totals.totalPriceSum.toFixed(2)}`
            );
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
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load categories.";
                $("#messageBox").text(msg);
            });
        }

        function loadProducts() {
            return $.getJSON(API_URLS.products).done(function(res) {
                products = res.data || [];
                $("#productDropdown").jqxDropDownList({ source: products });
                if (products.length === 0) {
                    $("#messageBox").text("No active products found.");
                }
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load products.";
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

        function registerQuickProduct() {
            const quickMessage = $("#quickProductMessage");
            quickMessage.removeClass("text-success text-danger").text("");

            const name = String($("#productNameInput").val() || "").trim();
            const serialNumber = String($("#productSerialInput").val() || "").trim();
            const brand = String($("#productBrandInput").val() || "").trim();

            if (!name) {
                quickMessage.addClass("text-danger").text("Product name is required.");
                return;
            }
            if (!serialNumber) {
                quickMessage.addClass("text-danger").text("Serial number is required.");
                return;
            }
            if (!selectedCategoryId) {
                quickMessage.addClass("text-danger").text("Please select category from category tree.");
                return;
            }

            const payload = {
                name,
                serial_number: serialNumber || null,
                category_id: Number(selectedCategoryId),
                brand: brand || null
            };

            $.ajax({
                url: API_URLS.products,
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify(payload)
            }).done(function (res) {
                const createdId = Number(res.data?.id || 0);
                quickMessage.addClass("text-success").text("New product is created.");

                $("#productNameInput").val("");
                $("#productSerialInput").val("");
                $("#productBrandInput").val("");

                loadProducts().done(function () {
                    if (createdId > 0) {
                        $("#productDropdown").jqxDropDownList("selectItem", createdId);
                    }
                });
            }).fail(function (xhr) {
                quickMessage.addClass("text-danger").text(xhr.responseJSON?.message || "Failed to register product.");
            });
        }

        function filterProductsByCategory(categoryId) {
            const filtered = Number(categoryId) > 0
                ? products.filter(p => Number(p.category_id) === Number(categoryId))
                : products;

            $("#productDropdown").jqxDropDownList({ source: filtered });
            $("#productDropdown").jqxDropDownList("clearSelection");
            productVariants = [];
        }

        function loadVariantsByProduct(productId) {
            if (!productId) {
                productVariants = [];
                selectedSizeCount = 0;
                $("#sizeSelector").jqxDropDownList({ source: FIXED_SIZES });
                $("#sizeSelector").jqxDropDownList("uncheckAll");
                recalcTotalUnits();
                return $.Deferred().resolve().promise();
            }

            return $.getJSON(API_URLS.variants, { product_id: productId }).done(function(res) {
                productVariants = (res.data || []).map(v => ({
                    ...v,
                    variant_key: String(v.id),
                    variant_label: `${v.product_name || "-"} | ${v.sku || "-"} | Size: ${v.size_value || "-"}`
                }));
                $("#sizeSelector").jqxDropDownList({ source: FIXED_SIZES });
                $("#sizeSelector").jqxDropDownList("uncheckAll");
                selectedSizeCount = 0;
                recalcTotalUnits();
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load product variants.";
                $("#messageBox").text(msg);
            });
        }

        function recalcTotalUnits() {
            const setsCount = Number($("#setsCountInput").jqxNumberInput("val") || 0);
            const totalUnits = Math.max(setsCount, 0) * Math.max(selectedSizeCount, 0);
            $("#totalUnitsInput").jqxNumberInput("val", totalUnits);
            recalcTotalPrice();
        }

        function recalcTotalPrice() {
            const unitPrice = Number($("#unitPriceInput").jqxNumberInput("val") || 0);
            const totalUnits = Number($("#totalUnitsInput").jqxNumberInput("val") || 0);
            const totalPrice = Number((Math.max(unitPrice, 0) * Math.max(totalUnits, 0)).toFixed(2));
            $("#totalPriceInput").jqxNumberInput("val", totalPrice);
        }

        function addItemRow() {
            const selectedProduct = $("#productDropdown").jqxDropDownList("getSelectedItem");
            if (!selectedProduct) {
                $("#messageBox").text("Please select a product.");
                return;
            }

            const selectedProductId = Number(selectedProduct.value);
            const productInfo = products.find(p => Number(p.id) === selectedProductId) || {};
            const checkedSizes = $("#sizeSelector").jqxDropDownList("getCheckedItems") || [];
            if (checkedSizes.length === 0) {
                $("#messageBox").text("Please select at least one size.");
                return;
            }

            const setsCount = Number($("#setsCountInput").jqxNumberInput("val") || 0);
            if (setsCount <= 0) {
                $("#messageBox").text("Sets count must be greater than 0.");
                return;
            }

            const unitCost = Number($("#unitPriceInput").jqxNumberInput("val") || 0);
            const unitsCount = Number($("#totalUnitsInput").jqxNumberInput("val") || 0);
            const totalPrice = Number($("#totalPriceInput").jqxNumberInput("val") || 0);
            const styleValue = String($("#productStyleInput").val() || "").trim();
            const selectedWarehouse = $("#purchaseWarehouseDropdown").jqxDropDownList("getSelectedItem");
            const warehouseId = Number(selectedWarehouse?.value || 0);
            if (!warehouseId) {
                $("#messageBox").text("Please select warehouse.");
                return;
            }
            const firstVariant = productVariants[0] || {};
            const rowData = {
                product_id: selectedProductId,
                product_variant_id: Number(firstVariant.id || 0),
                product_name: selectedProduct.label || productInfo.name || "",
                sku: String(productInfo.serial_number || ""),
                brand: String(productInfo.brand || ""),
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
            $("#messageBox").text("Product row added.");
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

            const payload = {
                supplier_id: supplierId,
                purchase_date: purchaseDate,
                notes: $("#notesInput").val(),
                items: validItems
            };
            const totals = getGridTotals();
            $("#confirmTotalUnitsCount").text(totals.totalUnitsCount);
            $("#confirmTotalPriceSum").text(totals.totalPriceSum.toFixed(2));
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
            loadProducts();
            loadWarehouses();
            confirmPurchaseModal = new bootstrap.Modal(document.getElementById("purchaseConfirmModal"));

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
                filterProductsByCategory(selectedCategoryId);
            });

            $("#productDropdown").on("select", function (event) {
                const item = event.args?.item;
                if (!item) {
                    return;
                }
                loadVariantsByProduct(Number(item.value));
            });

            $("#sizeSelector").on("checkChange", function () {
                const checkedItems = $("#sizeSelector").jqxDropDownList("getCheckedItems") || [];
                selectedSizeCount = checkedItems.length;
                recalcTotalUnits();
            });

            $("#setsCountInput").on("valueChanged", recalcTotalUnits);
            $("#unitPriceInput").on("valueChanged", recalcTotalPrice);

            $("#addProductsBtn").on("click", addItemRow);
            $("#savePurchaseBtn").on("click", savePurchase);
            $("#quickProductForm").on("submit", function (event) {
                event.preventDefault();
                registerQuickProduct();
            });
        });
</script>
<?= $this->endSection() ?>
