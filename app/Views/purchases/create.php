<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>New Purchase<?= $this->endSection() ?>

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
                    <div class="col-12 col-md-4">
                        <label class="form-label text-secondary mb-1">Product</label>
                        <div id="productDropdown"></div>
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

                <div class="row g-3 mb-3 align-items-end">
                    <div class="col-12 col-md-auto">
                        <input type="button" id="addItemRowBtn" value="Add Item Row">
                    </div>
                    <div class="col-12 col-md-auto">
                        <input type="button" id="removeItemRowBtn" value="Remove Selected Row">
                    </div>
                    <div class="col-12 col-md">
                        <small class="text-muted">Edit item rows directly in table (variant, qty, unit cost).</small>
                    </div>
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
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            suppliers: "<?= site_url('api/suppliers') ?>",
            categories: "<?= site_url('api/categories') ?>",
            products: "<?= site_url('api/products') ?>",
            variants: "<?= site_url('api/product-variants') ?>",
            purchases: "<?= site_url('api/purchases') ?>"
        };

        const items = [];
        let suppliers = [];
        let categories = [];
        let products = [];
        let productVariants = [];
        let selectedCategoryId = 0;

        function initWidgets() {
            $("#supplierDropdown").jqxDropDownList({ width: 280, height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select supplier" });
            $("#purchaseDate").jqxDateTimeInput({ width: 220, height: 34, formatString: "yyyy-MM-dd HH:mm:ss" });
            $("#categoryDropDownButton").jqxDropDownButton({ width: "100%", height: 34 });
            $("#categoryDropDownButton").jqxDropDownButton("setContent", '<div style="position: relative; margin-left: 3px; margin-top: 5px;" class="text-muted">Select category</div>');
            $("#productDropdown").jqxDropDownList({ width: 280, height: 34, displayMember: "name", valueMember: "id", placeHolder: "Select product" });
            $("#notesInput").jqxInput({ width: 420, height: 34 });
            $("#addItemRowBtn").jqxButton({ width: 150, height: 34, theme: "base" });
            $("#removeItemRowBtn").jqxButton({ width: 190, height: 34, theme: "base" });
            $("#savePurchaseBtn").jqxButton({ width: 140, height: 34, theme: "base" });

            $("#itemsGrid").jqxGrid({
                width: "100%",
                height: 220,
                source: new $.jqx.dataAdapter({ localdata: items, datatype: "array" }),
                editable: true,
                editmode: "click",
                selectionmode: "singlerow",
                columnsresize: true,
                columns: [
                    {
                        text: "Product Name",
                        datafield: "variant_key",
                        width: 260,
                        columntype: "dropdownlist",
                        createeditor: function (row, cellvalue, editor) {
                            editor.jqxDropDownList({
                                source: productVariants,
                                displayMember: "variant_label",
                                valueMember: "variant_key",
                                placeHolder: "Select variant"
                            });
                        },
                        initeditor: function (row, cellvalue, editor) {
                            editor.jqxDropDownList("clearSelection");
                            if (cellvalue) {
                                editor.jqxDropDownList("selectItem", cellvalue);
                            }
                        }
                    },
                    { text: "Product Number", datafield: "sku", width: 170, editable: false },
                    { text: "Brand Cost", datafield: "unit_cost", width: 130, columntype: "numberinput", cellsformat: "f2", cellsalign: "right" },
                    { text: "Qty", datafield: "qty", width: 80, columntype: "numberinput", cellsalign: "right" },
                    { text: "Sizes", datafield: "size_value", width: 110, editable: false },
                    { text: "Line Total", datafield: "line_total", cellsformat: "f2", editable: false, cellsalign: "right" }
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
            items.length = 0;
            refreshItemsGrid();
        }

        function loadVariantsByProduct(productId) {
            if (!productId) {
                productVariants = [];
                return $.Deferred().resolve().promise();
            }

            return $.getJSON(API_URLS.variants, { product_id: productId }).done(function(res) {
                productVariants = (res.data || []).map(v => ({
                    ...v,
                    variant_key: String(v.id),
                    variant_label: `${v.product_name || "-"} | ${v.sku || "-"} | Size: ${v.size_value || "-"}`
                }));
                items.length = 0;
                refreshItemsGrid();
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load product variants.";
                $("#messageBox").text(msg);
            });
        }

        function recalcLineTotal(rowIndex) {
            const row = $("#itemsGrid").jqxGrid("getrowdata", rowIndex);
            if (!row) {
                return;
            }
            const qty = Number(row.qty || 0);
            const unitCost = Number(row.unit_cost || 0);
            const lineTotal = Number((Math.max(qty, 0) * Math.max(unitCost, 0)).toFixed(2));
            $("#itemsGrid").jqxGrid("setcellvalue", rowIndex, "line_total", lineTotal);
        }

        function addItemRow() {
            if (productVariants.length === 0) {
                $("#messageBox").text("Select a product with variants first.");
                return;
            }

            const variant = productVariants[0];
            const rowData = {
                variant_key: variant.variant_key,
                product_variant_id: Number(variant.id),
                size_value: variant.size_value || "",
                sku: variant.sku || "",
                product_name: variant.product_name || "",
                qty: 1,
                unit_cost: 0,
                line_total: 0
            };
            $("#itemsGrid").jqxGrid("addrow", null, rowData);
        }

        function removeSelectedRow() {
            const index = $("#itemsGrid").jqxGrid("getselectedrowindex");
            if (index === -1) {
                $("#messageBox").text("Select an item row to remove.");
                return;
            }
            const rowId = $("#itemsGrid").jqxGrid("getrowid", index);
            $("#itemsGrid").jqxGrid("deleterow", rowId);
        }

        function getValidItems() {
            const rows = $("#itemsGrid").jqxGrid("getrows") || [];
            return rows
                .filter(r => Number(r.product_variant_id) > 0 && Number(r.qty) > 0)
                .map(r => ({
                    product_variant_id: Number(r.product_variant_id),
                    qty: Number(r.qty),
                    unit_cost: Number(r.unit_cost || 0),
                    discount_amount: 0
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

        $(function() {
            initWidgets();
            loadSuppliers();
            loadCategories();
            loadProducts();

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

            $("#itemsGrid").on("cellvaluechanged", function (event) {
                const rowIndex = event.args.rowindex;
                const datafield = event.args.datafield;
                const newValue = event.args.newvalue;

                if (datafield === "variant_key") {
                    const variant = productVariants.find(v => String(v.variant_key) === String(newValue));
                    if (variant) {
                        $("#itemsGrid").jqxGrid("setcellvalue", rowIndex, "product_variant_id", Number(variant.id));
                        $("#itemsGrid").jqxGrid("setcellvalue", rowIndex, "size_value", variant.size_value || "");
                        $("#itemsGrid").jqxGrid("setcellvalue", rowIndex, "sku", variant.sku || "");
                        $("#itemsGrid").jqxGrid("setcellvalue", rowIndex, "product_name", variant.product_name || "");
                    }
                }

                if (datafield === "qty" || datafield === "unit_cost" || datafield === "variant_key") {
                    recalcLineTotal(rowIndex);
                }
            });

            $("#addItemRowBtn").on("click", addItemRow);
            $("#removeItemRowBtn").on("click", removeSelectedRow);
            $("#savePurchaseBtn").on("click", savePurchase);
            $("#quickProductForm").on("submit", function (event) {
                event.preventDefault();
                registerQuickProduct();
            });
        });
</script>
<?= $this->endSection() ?>
