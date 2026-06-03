<?= $this->extend('layout/main_layout') ?>

<?= $this->section('title') ?>Inventory<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    #inventoryGrid .jqx-grid-cell-edit .jqx-numberinput {
        height: 100% !important;
        margin: 0 !important;
    }
    #inventoryGrid .jqx-grid-cell-edit .jqx-numberinput input {
        height: 100% !important;
        margin: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        box-sizing: border-box;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <div class="container-fluid px-5 py-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold mb-1">Inventory</h1>
                <!-- <p class="text-muted mb-0">Track current stock by product variant.</p> -->
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-12 col-md-3">
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search by product name, serial number, or style">
                    </div>
                    <div class="col-12 col-md-2">
                        <select id="filterWarehouse" class="form-select">
                            <option value="">All warehouses</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <div id="filterTags"></div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="applyInventoryFilterBtn" class="btn btn-primary">Filter</button>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="resetInventoryFilterBtn" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
                <div id="inventoryMessage" class="small fw-semibold mb-2"></div>
                <div id="inventoryGrid"></div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
        const API_URLS = {
            inventory: "<?= site_url('api/inventory') ?>",
            warehouses: "<?= site_url('api/warehouses') ?>",
            tags: "<?= site_url('api/tags') ?>"
        };

        let savingSellingPrice = false;
        let filterTagIds = new Set();
        let filterTagSyncLock = false;

        function setInventoryMessage(msg, isError = false) {
            const box = $("#inventoryMessage");
            box.text(msg || "");
            box.removeClass("text-success text-danger");
            if (msg) {
                box.addClass(isError ? "text-danger" : "text-success");
            }
        }

        function saveSellingPrice(rowIndex, sellingPrice) {
            if (savingSellingPrice) {
                return;
            }

            const row = $("#inventoryGrid").jqxGrid("getrowdata", rowIndex);
            const variantId = Number(row?.variant_id || 0);
            const price = Math.max(0, Number(sellingPrice || 0));

            if (variantId < 1) {
                return;
            }

            savingSellingPrice = true;
            $.ajax({
                url: `${API_URLS.inventory}/variant/${variantId}/selling-price`,
                method: "PUT",
                contentType: "application/json",
                data: JSON.stringify({ selling_price: price })
            }).done(function () {
                setInventoryMessage("");
                setMessage("Selling price saved.", false);
            }).fail(function (xhr) {
                const msg = xhr.responseJSON?.message || "Failed to update selling price.";
                setInventoryMessage(msg, true);
                setMessage(msg, true);
                loadInventory();
            }).always(function () {
                savingSellingPrice = false;
            });
        }

        function initWidgets() {
            $("#inventoryGrid").jqxGrid({
                width: "100%",
                height: 520,
                columnsresize: true,
                filterable: true,
                showfilterrow: true,
                editable: true,
                editmode: "click",
                source: new $.jqx.dataAdapter({ localdata: [], datatype: "array" }),
                columns: [
                    {
                        text: "#",
                        width: 50,
                        editable: false,
                        sortable: false,
                        filterable: false,
                        cellsrenderer: function (row) {
                            return `<div class="px-2 py-1">${row + 1}</div>`;
                        }
                    },
                    { text: "Product", datafield: "product_name", width: 220 },
                    { text: "Product Number", datafield: "product_number", width: 150 },
                    { text: "Brand", datafield: "brand", width: 120 },
                    { text: "Style", datafield: "style", width: 180 },
                    { text: "Size", datafield: "size_value", width: 90 },
                    // { text: "SKU", datafield: "sku", width: 150 },
                    { text: "Warehouse", datafield: "warehouse_name", width: 140 },
                    // { text: "Location", datafield: "warehouse_location", width: 150 },
                    { text: "Qty", datafield: "quantity", width: 90, cellsalign: "right" },
                    // { text: "Reserved", datafield: "reserved_quantity", width: 90, cellsalign: "right" },
                    { text: "Cost Price", datafield: "cost_price", width: 110, cellsformat: "f2", cellsalign: "right" },
                    {
                        text: "Selling Price",
                        datafield: "selling_price",
                        width: 120,
                        cellsformat: "f2",
                        cellsalign: "right",
                        columntype: "numberinput",
                        editable: true
                    }
                ]
            });

            function onSellingPriceEdited(event) {
                if (event.args.datafield !== "selling_price") {
                    return;
                }
                saveSellingPrice(event.args.rowindex, event.args.value);
            }

            $("#inventoryGrid").on("cellendedit", onSellingPriceEdited);
            $("#inventoryGrid").on("cellvaluechanged", onSellingPriceEdited);
        }

        function getSelectedFilterTagIds() {
            return Array.from(filterTagIds);
        }

        function syncFilterTagChecks() {
            if (!$("#filterTags").data("jqxDropDownList")) {
                return;
            }

            filterTagSyncLock = true;
            const items = $("#filterTags").jqxDropDownList("getItems") || [];
            items.forEach(function (item, index) {
                const id = Number(item.value || 0);
                if (filterTagIds.has(id)) {
                    $("#filterTags").jqxDropDownList("checkIndex", index);
                } else {
                    $("#filterTags").jqxDropDownList("uncheckIndex", index);
                }
            });
            filterTagSyncLock = false;
        }

        function initFilterTags() {
            $("#filterTags").jqxDropDownList({
                width: "100%",
                height: 38,
                displayMember: "name",
                valueMember: "id",
                placeHolder: "Filter by tags",
                checkboxes: true,
                source: []
            });

            $("#filterTags").on("checkChange", function (event) {
                if (filterTagSyncLock) {
                    return;
                }

                const id = Number(event.args?.item?.value || 0);
                if (id < 1) {
                    return;
                }

                if (event.args.checked) {
                    filterTagIds.add(id);
                } else {
                    filterTagIds.delete(id);
                }
            });
        }

        function loadTags() {
            return $.getJSON(API_URLS.tags).done(function (res) {
                $("#filterTags").jqxDropDownList({ source: res.data || [] });
                syncFilterTagChecks();
            });
        }

        function loadWarehouses() {
            return $.getJSON(API_URLS.warehouses).done(function (res) {
                const $select = $("#filterWarehouse");
                const current = $select.val();
                $select.find("option:not(:first)").remove();
                (res.data || []).forEach(function (row) {
                    $select.append(
                        $("<option></option>").attr("value", row.id).text(row.name || "")
                    );
                });
                $select.val(current || "");
            });
        }

        function loadInventory() {
            const search = String($("#filterSearch").val() || "").trim();
            const warehouseId = String($("#filterWarehouse").val() || "").trim();
            const tagIds = getSelectedFilterTagIds();
            const params = {};
            if (search) {
                params.q = search;
            }
            if (warehouseId) {
                params.warehouse_id = warehouseId;
            }
            if (tagIds.length) {
                params.tag_ids = tagIds.join(",");
            }

            return $.getJSON(API_URLS.inventory, params).done(function(res) {
                const rows = (res.data || []).map(function (row) {
                    return {
                        ...row,
                        selling_price: Number(row.selling_price || 0)
                    };
                });
                const source = { localdata: rows, datatype: "array" };
                $("#inventoryGrid").jqxGrid({ source: new $.jqx.dataAdapter(source) });
                setInventoryMessage("");
            }).fail(function(xhr) {
                const msg = xhr.responseJSON?.message || "Failed to load inventory.";
                console.error(msg);
            });
        }

        $(function() {
            initWidgets();
            initFilterTags();
            $.when(loadWarehouses(), loadTags()).always(loadInventory);
            $("#applyInventoryFilterBtn").on("click", loadInventory);
            $("#resetInventoryFilterBtn").on("click", function () {
                $("#filterSearch").val("");
                $("#filterWarehouse").val("");
                filterTagIds.clear();
                syncFilterTagChecks();
                loadInventory();
            });
            $("#filterSearch").on("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    loadInventory();
                }
            });
        });
</script>
<?= $this->endSection() ?>
